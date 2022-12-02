# Notifications package for the Spiral Framework

[![PHP](https://img.shields.io/packagist/php-v/spiral-packages/notifications.svg?style=flat-square)](https://packagist.org/packages/spiral-packages/notifications)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/spiral-packages/notifications.svg?style=flat-square)](https://packagist.org/packages/spiral-packages/notifications)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/spiral-packages/notifications/run-tests?label=tests&style=flat-square)](https://github.com/spiral-packages/notifications/actions?query=workflow%3Arun-tests+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/spiral-packages/notifications.svg?style=flat-square)](https://packagist.org/packages/spiral-packages/notifications)

The package provides support for sending notifications from the Spiral Dramework across a variety of delivery channels,
including [email](https://symfony.com/doc/current/mailer.html#using-built-in-transports),
[SMS](https://symfony.com/doc/current/notifier.html#sms-channel),
[chat](https://symfony.com/doc/current/notifier.html#chat-channel)
and [push](https://symfony.com/doc/current/notifier.html#push-channel).

Typically, notifications should be short, informational messages that notify users of something that occurred in your
application.

## Requirements

Make sure that your server is configured with following PHP version and extensions:

- PHP 8.1+
- Spiral framework 3.0+

## Installation

You can install the package via composer:

```bash
composer require spiral-packages/notifications
```

After package install you need to register bootloader from the package.

```php
protected const LOAD = [
    // ...
    \Spiral\Notifications\Bootloader\NotificationsBootloader::class,
];
```

## Usage

At first need create config file `app/config/notifications.php`. In this file, you should specify channels for
notifications.

```php
<?php

declare(strict_types=1);

use Symfony\Component\Notifier\Channel\BrowserChannel;
use Symfony\Component\Notifier\Channel\ChatChannel;
use Symfony\Component\Notifier\Channel\EmailChannel;
use Symfony\Component\Notifier\Channel\PushChannel;
use Symfony\Component\Notifier\Channel\SmsChannel;

return [
    'queueConnection' => env('NOTIFICATIONS_QUEUE_CONNECTION', 'sync'),

    'channels' => [
        'sms' => [
            'type' => 'sms',
            'transport' => 'nexmo',
        ],
        'email' => [
            'type' => 'email',
            'transport' => 'smtp',
        ],
        'roundrobin_email' => [
            'type' => 'email',
            'transport' => ['smtp', 'smtp_1'], // will be used roundrobin algorithm
        ],
        'chat/slack' => [
            'type' => 'chat',
            'transport' => 'slack',
        ],
        'push/firebase' => [
            'type' => 'push',
            'transport' => 'firebase',
        ],
    ],

    // Full list of available transports you can see by following link below
    // https://symfony.com/doc/current/notifier.html#channels-chatters-texters-email-browser-and-push
    // https://symfony.com/doc/current/mailer.html#using-built-in-transports
    'transports' => [
        'nexmo' => env('NOTIFICATIONS_NEXMO_DSN'),          // nexmo://KEY:SECRET@default?from=FROM
        'smtp' => env('NOTIFICATIONS_EMAIL_DSN'),          // smtp://user:pass@smtp.example.com:25
        'smtp_1' => env('NOTIFICATIONS_EMAIL_DSN'),          // smtp://user:pass@smtp.example.com:25
        'slack' => env('NOTIFICATIONS_SLACK_DSN'),          // slack://TOKEN@default?channel=CHANNEL
        'firebase' => env('NOTIFICATIONS_FIREBASE_DSN'),    // firebase://USERNAME:PASSWORD@default
        // ..
    ],

    'policies' => [
        'urgent' => ['sms', 'chat/slack', 'email'],
        'high' => ['chat/slack', 'push/firebase'],
    ],

    'typeAliases' => [
        'browser' => BrowserChannel::class,
        'chat' => ChatChannel::class,
        'email' => EmailChannel::class,
        'push' => PushChannel::class,
        'sms' => SmsChannel::class,
    ],
];
```

Add `Symfony\Component\Notifier\Recipient\RecipientInterface` interface to the recipients.

```php
use Symfony\Component\Notifier\Recipient\RecipientInterface;
use Symfony\Component\Notifier\Recipient\SmsRecipientInterface;

class User implements RecipientInterface, SmsRecipientInterface
{
    // ...

    public function getPhone(): string
    {
        return '+8(000)000-00-00';
    }
}
```

Extend your notifications objects form `Symfony\Component\Notifier\Notification\Notification` class

```php
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\Notification\SmsNotificationInterface;
use Symfony\Component\Notifier\Message\SmsMessage;

class UserBannedNotification extends Notification implements SmsNotificationInterface
{
    public function getChannels(RecipientInterface $recipient): array
    {
        return ['sms', 'chat'];
    }

    public function asSmsMessage(SmsRecipientInterface $recipient, string $transport = null): ?SmsMessage
    {
        return SmsMessage::fromNotification($this, $recipient);
    }
}
```

Use `getChannels` method if you want to send a notification to specific channels:

```php
public function getChannels(RecipientInterface $recipient): array
{
    return ['sms', 'chat/slack'];
}
```

Or use `getImportance` if you want to send a notification by urgency:

```php
public function getImportance(): string
{
    return 'urgent';
}
```

And then you can send your notification via `Notifier`

```php
use Symfony\Component\Notifier\NotifierInterface;

class BanUserService {

    public function __construct(
        private UserRepository $repository
        private NotifierInterface $notifier
    ) {}

    public function handle(string $userUuid): void
    {
        $user = $this->repository->findByPK($userUuid);

        // Send now
        $this->notifier->send(
            new UserBannedNotification('Your profile banned for activity that violates rules'),
            $user
        );

        // Send queued
        // Queued notification will be sent via `queueConnection` from notification config.
        $this->notifier->sendQueued(
            new UserBannedNotification('Your profile banned for activity that violates rules'),
            $user
        );
    }
}
```

### Custom notification transports

If you want to use custom notification transport like [`spacetab-io/smsaero-notifier`](https://github.com/spacetab-io/smsaero-notifier-php)
you can register it in `Spiral\Notifications\NotificationTransportRegistryInterface`.

```php
use Spiral\Boot\Bootloader\Bootloader;
use Spiral\Notifications\NotificationTransportResolver;
use Spacetab\SmsaeroNotifier\SmsaeroTransportFactory;

class MyBootloader extends Bootloader
{
    public function boot(NotificationTransportRegistryInterface $registry): void
    {
        $resolver->registerTransport(new SmsaeroTransportFactory());
    }
}
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [butschster](https://github.com/spiral-packages)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
