<?php

declare(strict_types=1);

namespace Spiral\Notifications;

use Spiral\Core\Container\SingletonInterface;
use Symfony\Component\Notifier\Exception\UnsupportedSchemeException;
use Symfony\Component\Notifier\Transport;
use Symfony\Component\Notifier\Transport\TransportFactoryInterface;

final class NotificationTransportResolver implements SingletonInterface
{
    /** @var TransportFactoryInterface[] $transports */
    public function __construct(
        private array $transports = []
    ) {
    }

    public function registerTransport(TransportFactoryInterface $factory): void
    {
        $this->transports[] = $factory;
    }

    /**
     * @throws UnsupportedSchemeException
     */
    public function resolve(Transport\Dsn $dsn): Transport\TransportInterface
    {
        foreach ($this->transports as $transport) {
            if ($transport->supports($dsn)) {
                return $transport->create($dsn);
            }
        }

        return Transport::fromDsn($dsn->getOriginalDsn());
    }
}
