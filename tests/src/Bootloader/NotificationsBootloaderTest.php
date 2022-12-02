<?php

declare(strict_types=1);

namespace Spiral\Notifications\Tests\Bootloader;

use Spiral\Notifications\ChannelManager;
use Spiral\Notifications\Config\NotificationsConfig;
use Spiral\Notifications\NotificationTransportRegistryInterface;
use Spiral\Notifications\NotificationTransportResolver;
use Spiral\Notifications\NotificationTransportResolverInterface;
use Spiral\Notifications\Notifier;
use Spiral\Notifications\Tests\TestCase;
use Spiral\Queue\QueueConnectionProviderInterface;
use Symfony\Component\Notifier\Channel\ChannelPolicyInterface;
use Symfony\Component\Notifier\NotifierInterface;

final class NotificationsBootloaderTest extends TestCase
{
    public function testQueueProviderShouldBeBound(): void
    {
        $this->assertContainerBound(QueueConnectionProviderInterface::class);
    }

    public function testChannelManagerShouldBeBound(): void
    {
        $this->assertContainerBoundAsSingleton(
            ChannelManager::class,
            ChannelManager::class
        );
    }

    public function testNotificationTransportResolverShouldBeBound(): void
    {
        $this->assertContainerBoundAsSingleton(
            NotificationTransportResolverInterface::class,
            NotificationTransportResolver::class
        );

        $this->assertContainerBoundAsSingleton(
            NotificationTransportRegistryInterface::class,
            NotificationTransportResolver::class
        );
    }

    public function testNotifierShouldBeBound()
    {
        $this->assertContainerBoundAsSingleton(
            NotifierInterface::class,
            Notifier::class
        );

        $this->assertContainerBoundAsSingleton(
            Notifier::class,
            NotifierInterface::class
        );
    }

    public function testChannelPolicyShouldBeBound()
    {
        $this->assertContainerBound(ChannelPolicyInterface::class);
    }

    public function testConfigShouldBeConfigured()
    {
        $config = $this->getConfig(NotificationsConfig::CONFIG);

        $this->assertSame('sync', $config['queueConnection']);
    }
}
