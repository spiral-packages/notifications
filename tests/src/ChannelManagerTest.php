<?php

declare(strict_types=1);

namespace Spiral\Notifications\Tests;

use Mockery as m;
use Spiral\Core\FactoryInterface;
use Spiral\Notifications\ChannelManager;
use Spiral\Notifications\Config\NotificationsConfig;
use Spiral\Notifications\NotificationTransportResolver;
use Spiral\SendIt\Config\MailerConfig;
use Symfony\Component\Mailer\Transport\RoundRobinTransport as MailerRoundRobinTransport;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Notifier\Bridge\Firebase\FirebaseTransport;
use Symfony\Component\Notifier\Channel\ChannelInterface;
use Symfony\Component\Notifier\Channel\EmailChannel;
use Symfony\Component\Notifier\Exception\UnsupportedSchemeException;
use Symfony\Component\Notifier\Transport\RoundRobinTransport;

final class ChannelManagerTest extends TestCase
{
    private ChannelManager $manager;
    private m\LegacyMockInterface|m\MockInterface|FactoryInterface $factory;

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
            new NotificationTransportResolver()
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
        $this->factory->shouldReceive('make')->once()
            ->withArgs(static function (string $type, array $args): bool {
                return $type === 'firebase'
                    && $args['transport'] instanceof FirebaseTransport
                    && (string)$args['transport'] === 'firebase://fcm.googleapis.com/fcm/send';
            })
            ->andReturn($channel = m::mock(ChannelInterface::class));

        $this->assertSame(
            $channel,
            $this->manager->getChannel('firebase')
        );
    }

    public function testGetsNotificationChannelWithMultipleTransport(): void
    {
        $this->factory->shouldReceive('make')->once()
            ->withArgs(static function (string $type, array $args): bool {
                return $type === 'firebase_multiple'
                    && $args['transport'] instanceof RoundRobinTransport;
            })
            ->andReturn($channel = m::mock(ChannelInterface::class));

        $this->assertSame(
            $channel,
            $this->manager->getChannel('firebase_multiple')
        );
    }

    public function testUnknownTransportTypeShouldThrowAnException(): void
    {
        $this->expectException(UnsupportedSchemeException::class);
        $this->expectErrorMessage('The "foo" scheme is not supported.');

        $this->manager->getChannel('unknown');
    }

    public function testUnsupportedTransportTypeShouldThrowAnException(): void
    {
        $this->expectException(UnsupportedSchemeException::class);
        $this->expectErrorMessage('Unable to send notification via "discord" as the bridge is not installed; try running "composer require symfony/discord-notifier".');

        $this->manager->getChannel('unsupported');
    }
}
