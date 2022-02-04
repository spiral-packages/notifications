<?php

declare(strict_types=1);

namespace Spiral\Notifications;

use Spiral\Core\Container;
use Spiral\Notifications\Config\NotificationsConfig;
use Spiral\SendIt\Config\MailerConfig;
use Symfony\Component\Notifier\Channel\ChannelInterface;
use Symfony\Component\Notifier\Channel\EmailChannel;
use Symfony\Component\Notifier\Transport;

final class ChannelManager
{
    /** @var array<non-empty-string, ChannelInterface> */
    private array $channels = [];

    public function __construct(
        private Container $container,
        private NotificationsConfig $config,
        private MailerConfig $mailerConfig,
    )
    {
    }

    public function getChannel(string $name): ?ChannelInterface
    {
        if (isset($this->channels[$name])) {
            return $this->channels[$name];
        }

        $channel = $this->config->getChannel($name);
        $dsn = $channel['transport']->getOriginalDsn();

        return $this->channels[$name] = match ($channel['type']) {
            EmailChannel::class => $this->container->make($channel['type'], [
                'transport' => \Symfony\Component\Mailer\Transport::fromDsn($dsn),
                'from' => $this->mailerConfig->getFromAddress()
            ]),
            default => $this->container->make($channel['type'], [
                'transport' => Transport::fromDsn($dsn),
            ])
        };
    }
}
