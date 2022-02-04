<?php

declare(strict_types=1);

namespace Spiral\Notifications\Tests\Bootloader;

use Spiral\Notifications\Config\NotificationsConfig;
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
