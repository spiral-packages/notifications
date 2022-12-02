<?php

declare(strict_types=1);

namespace Spiral\Notifications\Bootloader;

use Spiral\Boot\Bootloader\Bootloader;
use Spiral\Boot\EnvironmentInterface;
use Spiral\Config\ConfiguratorInterface;
use Spiral\Notifications\ChannelManager;
use Spiral\Notifications\Config\NotificationsConfig;
use Spiral\Notifications\NotificationTransportRegistryInterface;
use Spiral\Notifications\NotificationTransportResolver;
use Spiral\Notifications\NotificationTransportResolverInterface;
use Spiral\Notifications\Notifier;
use Spiral\Queue\Bootloader\QueueBootloader;
use Spiral\Queue\QueueConnectionProviderInterface;
use Symfony\Component\Notifier\Channel\BrowserChannel;
use Symfony\Component\Notifier\Channel\ChannelPolicy;
use Symfony\Component\Notifier\Channel\ChannelPolicyInterface;
use Symfony\Component\Notifier\Channel\ChatChannel;
use Symfony\Component\Notifier\Channel\EmailChannel;
use Symfony\Component\Notifier\Channel\PushChannel;
use Symfony\Component\Notifier\Channel\SmsChannel;
use Symfony\Component\Notifier\NotifierInterface;

class NotificationsBootloader extends Bootloader
{
    protected const DEPENDENCIES = [
        QueueBootloader::class,
    ];

    protected const SINGLETONS = [
        NotifierInterface::class => Notifier::class,
        ChannelPolicyInterface::class => [self::class, 'initChannelPolicy'],
        Notifier::class => [self::class, 'initNotifier'],
        ChannelManager::class => ChannelManager::class,
        NotificationTransportResolver::class => NotificationTransportResolver::class,
        NotificationTransportResolverInterface::class => NotificationTransportResolver::class,
        NotificationTransportRegistryInterface::class => NotificationTransportResolver::class,
    ];

    public function __construct(
        private readonly ConfiguratorInterface $config
    ) {
    }

    public function init(EnvironmentInterface $env): void
    {
        $this->config->setDefaults(
            NotificationsConfig::CONFIG,
            [
                'queueConnection' => $env->get('NOTIFICATIONS_QUEUE_CONNECTION', 'sync'),
                'channels' => [],
                'transports' => [],
                'policies' => [],
                'typeAliases' => [
                    'browser' => BrowserChannel::class,
                    'chat' => ChatChannel::class,
                    'email' => EmailChannel::class,
                    'push' => PushChannel::class,
                    'sms' => SmsChannel::class,
                ],
            ]
        );
    }

    private function initNotifier(
        ChannelManager $manager,
        QueueConnectionProviderInterface $connectionProvider,
        ChannelPolicyInterface $policy,
        NotificationsConfig $config
    ): NotifierInterface {
        return new Notifier($manager, $config, $connectionProvider, $policy);
    }

    private function initChannelPolicy(NotificationsConfig $config): ChannelPolicyInterface
    {
        return new ChannelPolicy(
            $config->getChannelPolicies()
        );
    }
}
