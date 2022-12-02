<?php

declare(strict_types=1);

namespace Spiral\Notifications\Tests;

use Mockery as m;
use Spiral\Notifications\NotificationTransportResolver;
use Symfony\Component\Notifier\Exception\UnsupportedSchemeException;
use Symfony\Component\Notifier\Transport\Dsn;
use Symfony\Component\Notifier\Transport\NullTransport;
use Symfony\Component\Notifier\Transport\TransportFactoryInterface;
use Symfony\Component\Notifier\Transport\TransportInterface;

final class NotificationTransportResolverTest extends TestCase
{
    public function testResolveCustomTransportTransport(): void
    {
        $resolver = new NotificationTransportResolver();

        $resolver->registerTransport($transportFactory = m::mock(TransportFactoryInterface::class));

        $dsn = new Dsn('test://user@password');

        $transportFactory->shouldReceive('supports')->with($dsn)->andReturnTrue();
        $transportFactory->shouldReceive('create')->with($dsn)->andReturn(
            $transport = m::mock(TransportInterface::class)
        );

        $this->assertSame($transport, $resolver->resolve($dsn));
    }

    public function testResolveDefaultTransport(): void
    {
        $resolver = new NotificationTransportResolver();
        $resolver->registerTransport($transportFactory = m::mock(TransportFactoryInterface::class));

        $dsn = new Dsn('null://user@password');

        $transportFactory->shouldReceive('supports')->with($dsn)->andReturnFalse();

        $this->assertInstanceOf(NullTransport::class, $resolver->resolve($dsn));
    }


    public function testUnknownTransportTypeShouldThrowAnException(): void
    {
        $this->expectException(UnsupportedSchemeException::class);
        $this->expectErrorMessage('The "unknown" scheme is not supported.');

        $resolver = new NotificationTransportResolver();
        $resolver->resolve( new Dsn('unknown://user@password'));
    }
}
