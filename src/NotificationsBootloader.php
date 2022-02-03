<?php

declare(strict_types=1);

namespace Spiral\Notifications;

use Spiral\Boot\Bootloader\Bootloader;
use Spiral\Boot\EnvironmentInterface;
use Spiral\Config\ConfiguratorInterface;
use Spiral\Notifications\Config\NotificationsConfig;
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
    ];

    public function __construct(private ConfiguratorInterface $config)
    {
    }

    public function boot(EnvironmentInterface $env): void
    {
        $this->config->setDefaults(
            NotificationsConfig::CONFIG,
            [
                'queueConnection' => null,

                'channels' => [

                    // 'sms' => [
                    //     'type' => 'sms',
                    //     'transport' => 'smsapi',
                    // ],

                    // 'chat/slack' => [
                    //     'type' => 'chat',
                    //     'transport' => 'slack',
                    // ],

                    // 'email' => [
                    //     'type' => 'email',
                    //     'transport' => 'slack',
                    // ],

                ],

                'transports' => [

                    // 'smsapi' => env('SMSAPI_DSN'),   // smsapi://TOKEN@default?from=FROM,
                    // 'email' => env('MAILER_DSN')     // smtp://user:pass@smtp.example.com:25

                ],

                'policies' => [

                    // 'urgent' => ['sms', 'chat/slack', 'email'],

                    // 'high' => ['chat/slack'],

                ],

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
        return new Notifier(
            $manager,
            $connectionProvider->getConnection(
                $config->getQueueConnection()
            ),
            $policy
        );
    }

    private function initChannelPolicy(NotificationsConfig $config): ChannelPolicyInterface
    {
        return new ChannelPolicy(
            $config->getChannelPolicies()
        );
    }
}