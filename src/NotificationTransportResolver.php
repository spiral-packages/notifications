<?php

declare(strict_types=1);

namespace Spiral\Notifications;

use Symfony\Component\Notifier\Transport;
use Symfony\Component\Notifier\Transport\TransportFactoryInterface;

final class NotificationTransportResolver implements NotificationTransportResolverInterface,
                                                     NotificationTransportRegistryInterface
{
    /** @var TransportFactoryInterface[] */
    private array $transports = [];

    /**
     * @param TransportFactoryInterface[] $transports
     */
    public function __construct(array $transports = [])
    {
        foreach ($transports as $transport) {
            $this->registerTransport($transport);
        }
    }

    public function registerTransport(TransportFactoryInterface $factory): void
    {
        $this->transports[] = $factory;
    }

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
