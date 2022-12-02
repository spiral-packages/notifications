<?php

declare(strict_types=1);

namespace Spiral\Notifications;

use Symfony\Component\Notifier\Transport\TransportFactoryInterface;

interface NotificationTransportRegistryInterface
{
    public function registerTransport(TransportFactoryInterface $factory): void;
}
