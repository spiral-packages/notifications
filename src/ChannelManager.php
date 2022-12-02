<?php

declare(strict_types=1);

namespace Spiral\Notifications;

use Spiral\Core\FactoryInterface;
use Spiral\Notifications\Config\NotificationsConfig;
use Spiral\SendIt\Config\MailerConfig;
use Symfony\Component\Mailer\Transport\RoundRobinTransport as MailerRoundRobinTransport;
use Symfony\Component\Mailer\Transport\TransportInterface as MailerTransportInterface;
use Symfony\Component\Notifier\Channel\ChannelInterface;
use Symfony\Component\Notifier\Channel\EmailChannel;
use Symfony\Component\Notifier\Transport;

final class ChannelManager
{
    /** @var array<non-empty-string, ChannelInterface> */
    private array $channels = [];

    public function __construct(
        private readonly FactoryInterface $factory,
        private readonly NotificationsConfig $config,
        private readonly MailerConfig $mailerConfig,
        private readonly NotificationTransportResolverInterface $transportResolver,
    ) {
    }

    public function getChannel(string $name): ?ChannelInterface
    {
        if (isset($this->channels[$name])) {
            return $this->channels[$name];
        }

        $channel = $this->config->getChannel($name);
        $dsns = $channel['transport'];

        if ($channel['type'] === EmailChannel::class) {
            if (\count($dsns) === 1) {
                $transport = $this->resolveMailerTransport($dsns[0]);
            } else {
                $transport = new MailerRoundRobinTransport(
                    \array_map(function (Transport\Dsn $dsn): MailerTransportInterface {
                        return $this->resolveMailerTransport($dsn);
                    }, $dsns)
                );
            }

            return $this->factory->make($channel['type'], [
                'transport' => $transport,
                'from' => $this->mailerConfig->getFromAddress(),
            ]);
        }

        if (\count($dsns) === 1) {
            $transport = $this->transportResolver->resolve($dsns[0]);
        } else {
            $transport = new Transport\RoundRobinTransport(
                \array_map(function (Transport\Dsn $dsn): Transport\TransportInterface {
                    return $this->transportResolver->resolve($dsn);
                }, $dsns)
            );
        }

        return $this->factory->make($channel['type'], [
            'transport' => $transport,
        ]);
    }

    private function resolveMailerTransport(Transport\Dsn $dsn): MailerTransportInterface
    {
        return \Symfony\Component\Mailer\Transport::fromDsn(
            $dsn->getOriginalDsn()
        );
    }
}
