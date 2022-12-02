<?php

declare(strict_types=1);

namespace Spiral\Notifications\Tests;

use Mockery as m;
use Spiral\Core\FactoryInterface;
use Spiral\Notifications\ChannelManager;
use Spiral\Notifications\Config\NotificationsConfig;
use Spiral\Notifications\NotificationTransportResolverInterface;
use Spiral\SendIt\Config\MailerConfig;
use Symfony\Component\Mailer\Transport\RoundRobinTransport as MailerRoundRobinTransport;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Notifier\Channel\ChannelInterface;
use Symfony\Component\Notifier\Channel\EmailChannel;
use Symfony\Component\Notifier\Transport\Dsn;
use Symfony\Component\Notifier\Transport\RoundRobinTransport;
use Symfony\Component\Notifier\Transport\TransportInterface;

final class ChannelManagerTest extends TestCase
{
    private ChannelManager $manager;
    private m\LegacyMockInterface|m\MockInterface|FactoryInterface $factory;
    private m\LegacyMockInterface|m\MockInterface|NotificationTransportResolverInterface $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $config = new NotificationsConfig([
            'channels' => [
                'email_single' => [
                    'type' => EmailChannel::class,
                    'transport' => 'gmail',
                ],
                'email_multiple' => [
                    'type' => EmailChannel::class,
                    'transport' => ['gmail', 'yahoo'],
                ],
                'firebase' => [
                    'type' => 'firebase',
                    'transport' => 'firebase',
                ],
                'firebase_multiple' => [
                    'type' => 'firebase_multiple',
                    'transport' => ['firebase1', 'firebase'],
                ],
                'unknown' => [
                    'type' => 'unknown',
                    'transport' => 'unknown',
                ],
                'unsupported' => [
                    'type' => 'unsupported',
                    'transport' => 'unsupported',
                ],
            ],
            'transports' => [
                'gmail' => 'smtp://gmail:pass@smtp.gmail.com:25',
                'yahoo' => 'smtp://yahoo:pass@smtp.yahoo.com:25',
                'firebase' => 'firebase://USERNAME:PASSWORD@default',
                'firebase1' => 'firebase://USERNAME1:PASSWORD@default',
                'unknown' => 'foo://USERNAME:PASSWORD@default',
                'unsupported' => 'discord://TOKEN@default?webhook_id=ID',
            ],
        ]);

        $this->manager = new ChannelManager(
            $this->factory = m::mock(FactoryInterface::class),
            $config,
            new MailerConfig([
                'dsn' => 'smtp://user:pass@smtp.example.com:25',
                'from' => 'info@site.com',
                'queue' => null,
                'pipeline' => null,
                'queueConnection' => null,
            ]),
            $this->resolver = m::mock(NotificationTransportResolverInterface::class),
        );
    }

    public function testGetsEmailChannelWithSingleTransport(): void
    {
        $this->factory->shouldReceive('make')->once()
            ->withArgs(static function (string $type, array $args): bool {
                return $type === EmailChannel::class
                    && $args['transport'] instanceof EsmtpTransport
                    && (string)$args['transport'] === 'smtp://smtp.gmail.com'
                    && $args['from'] === 'info@site.com';
            })
            ->andReturn($channel = m::mock(ChannelInterface::class));

        $this->assertSame(
            $channel,
            $this->manager->getChannel('email_single')
        );
    }

    public function testGetsEmailChannelWithMultipleTransport(): void
    {
        $this->factory->shouldReceive('make')->once()
            ->withArgs(static function (string $type, array $args): bool {
                return $type === EmailChannel::class
                    && $args['transport'] instanceof MailerRoundRobinTransport
                    && (string)$args['transport'] === 'roundrobin(smtp://smtp.gmail.com smtp://smtp.yahoo.com)'
                    && $args['from'] === 'info@site.com';
            })
            ->andReturn($channel = m::mock(ChannelInterface::class));

        $this->assertSame(
            $channel,
            $this->manager->getChannel('email_multiple')
        );
    }

    public function testGetsNotificationChannelWithSingleTransport(): void
    {
        $transport = m::mock(TransportInterface::class);
        $this->factory->shouldReceive('make')->once()
            ->with('firebase', ['transport' => $transport])
            ->andReturn($channel = m::mock(ChannelInterface::class));

        $this->resolver->shouldReceive('resolve')->once()->withArgs(static function (Dsn $dsn): bool {
            return $dsn->getOriginalDsn() === 'firebase://USERNAME:PASSWORD@default';
        })->andReturn($transport);

        $this->assertSame(
            $channel,
            $this->manager->getChannel('firebase')
        );
    }

    public function testGetsNotificationChannelWithMultipleTransport(): void
    {
        $transport1 = m::mock(TransportInterface::class);
        $transport2 = m::mock(TransportInterface::class);

        $this->factory->shouldReceive('make')->once()
            ->withArgs(static function (string $type, array $args): bool {
                return $type === 'firebase_multiple'
                    && $args['transport'] instanceof RoundRobinTransport;
            })
            ->andReturn($channel = m::mock(ChannelInterface::class));

        $this->resolver->shouldReceive('resolve')->once()->withArgs(static function (Dsn $dsn): bool {
            return $dsn->getOriginalDsn() === 'firebase://USERNAME:PASSWORD@default';
        })->andReturn($transport1);

        $this->resolver->shouldReceive('resolve')->once()->withArgs(static function (Dsn $dsn): bool {
            return $dsn->getOriginalDsn() === 'firebase://USERNAME1:PASSWORD@default';
        })->andReturn($transport2);

        $this->assertSame(
            $channel,
            $this->manager->getChannel('firebase_multiple')
        );
    }
}
