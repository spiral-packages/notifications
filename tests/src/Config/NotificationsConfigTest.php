<?php

declare(strict_types=1);

namespace Spiral\Notifications\Tests\Config;

use Spiral\Notifications\Config\NotificationsConfig;
use Spiral\Notifications\Exceptions\InvalidArgumentException;
use Spiral\Notifications\Exceptions\TransportException;
use Spiral\Notifications\Tests\TestCase;
use Symfony\Component\Notifier\Channel\EmailChannel;
use Symfony\Component\Notifier\Transport\Dsn;

final class NotificationsConfigTest extends TestCase
{
    public function testGetsUnknownChannelShouldThrowAnException(): void
    {
        $config = new NotificationsConfig([]);

        $this->expectException(TransportException::class);
        $this->expectErrorMessage('Channel with given name `foo` is not found.');

        $config->getChannel('foo');
    }

    public function testGetsChannelWithInvalidConfigShouldThrowAnException(): void
    {
        $config = new NotificationsConfig([
            'channels' => [
                'foo' => 'bar'
            ],
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectErrorMessage('Config for channel `foo` must be an array.');

        $config->getChannel('foo');
    }

    public function testGetsChannelWithoutTypeShouldThrowAnException(): void
    {
        $config = new NotificationsConfig([
            'channels' => [
                'foo' => []
            ],
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectErrorMessage('Config for channel `foo` should contain `type` key.');

        $config->getChannel('foo');
    }

    public function testGetsChannelWithoutTransportShouldThrowAnException(): void
    {
        $config = new NotificationsConfig([
            'channels' => [
                'foo' => [
                    'type' => 'bar'
                ]
            ],
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectErrorMessage('Config for channel `foo` should contain `transport` key.');

        $config->getChannel('foo');
    }

    public function testGetsChannel(): void
    {
        $config = new NotificationsConfig([
            'channels' => [
                'foo' => [
                    'type' => 'bar',
                    'transport' => 'bar'
                ]
            ],
            'transports' => [
                'bar' => 'foo://bar',
            ],
        ]);

        $channel = $config->getChannel('foo');

        $this->assertSame('bar', $channel['type']);
        $this->assertSame('foo://bar', $channel['transport'][0]->getOriginalDsn());
    }

    public function testGetsChannelWithTypeAlias(): void
    {
        $config = new NotificationsConfig([
            'channels' => [
                'foo' => [
                    'type' => 'bar',
                    'transport' => ['bar', 'foo']
                ]
            ],
            'transports' => [
                'foo' => 'foo://baz',
                'bar' => 'foo://bar',
            ],
            'typeAliases' => [
                'bar' => EmailChannel::class
            ]
        ]);

        $channel = $config->getChannel('foo');

        $this->assertSame(EmailChannel::class, $channel['type']);
        $this->assertSame('foo://bar', $channel['transport'][0]->getOriginalDsn());
        $this->assertSame('foo://baz', $channel['transport'][1]->getOriginalDsn());
    }

    public function testGetsQueueConnection(): void
    {
        $config = new NotificationsConfig([
            'queueConnection' => 'foo',
        ]);
        $this->assertSame('foo', $config->getQueueConnection());


        $config = new NotificationsConfig([]);
        $this->assertNull($config->getQueueConnection());
    }

    public function testGetsChannelPolicies(): void
    {
        $config = new NotificationsConfig([
            'policies' => ['foo'],
        ]);
        $this->assertSame(['foo'], $config->getChannelPolicies());


        $config = new NotificationsConfig([]);
        $this->assertSame([], $config->getChannelPolicies());
    }

    public function testGetsTransport(): void
    {
        $config = new NotificationsConfig([
            'transports' => [
                'foo' => 'foo://bar',
                'baz' => 'baz://bar',
            ],
        ]);

        $this->assertSame(['foo://bar',],
            \array_map(static function (Dsn $dsn): string {
                return $dsn->getOriginalDsn();
            }, $config->getTransport(['foo']))
        );

        $this->assertSame(['foo://bar', 'baz://bar',],
            \array_map(static function (Dsn $dsn): string {
                return $dsn->getOriginalDsn();
            }, $config->getTransport(['foo', 'baz']))
        );
    }

    public function testGetsUnknownTransportShouldThrowAnException(): void
    {
        $this->expectException(TransportException::class);
        $this->expectErrorMessage('Transport with given name `foo` is not found.');

        $config = new NotificationsConfig([
            'transports' => [],
        ]);

        $config->getTransport(['foo']);
    }

    public function testGetsInvalidTransportShouldThrowAnException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectErrorMessage('Config for transport `foo` must be a DSN string');

        $config = new NotificationsConfig([
            'transports' => [
                'foo' => [],
            ],
        ]);

        $config->getTransport(['foo']);
    }
}
