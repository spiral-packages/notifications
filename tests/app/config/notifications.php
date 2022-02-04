<?php

declare(strict_types=1);

use Symfony\Component\Notifier\Channel\BrowserChannel;
use Symfony\Component\Notifier\Channel\ChatChannel;
use Symfony\Component\Notifier\Channel\EmailChannel;
use Symfony\Component\Notifier\Channel\PushChannel;
use Symfony\Component\Notifier\Channel\SmsChannel;

return [
    'queueConnection' => 'sync',

    'channels' => [
        'sms' => [
            'type' => 'sms',
            'transport' => 'smsapi',
        ],

        'email' => [
            'type' => 'email',
            'transport' => 'email',
        ],
    ],

    'transports' => [
        'smsapi' => 'smsapi://TOKEN@default?from=FROM',
        'email' => 'smtp://user:pass@smtp.example.com:25',
    ],

    'policies' => [
        'urgent' => ['sms', 'chat/slack', 'email'],
        'high' => ['chat/slack'],
    ],

    'typeAliases' => [
        'browser' => BrowserChannel::class,
        'chat' => ChatChannel::class,
        'email' => EmailChannel::class,
        'push' => PushChannel::class,
        'sms' => SmsChannel::class,
    ],
];
