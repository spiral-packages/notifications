<?php

declare(strict_types=1);

namespace Spiral\Notifications;

use Symfony\Component\Notifier\Exception\UnsupportedSchemeException;
use Symfony\Component\Notifier\Transport;

interface NotificationTransportResolverInterface
{
    /**
     * @throws UnsupportedSchemeException
     */
    public function resolve(Transport\Dsn $dsn): Transport\TransportInterface;
}
