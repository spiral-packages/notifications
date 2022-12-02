<?php

declare(strict_types=1);

namespace Spiral\Notifications\Config;

use Spiral\Core\InjectableConfig;
use Spiral\Notifications\Exceptions\InvalidArgumentException;
use Spiral\Notifications\Exceptions\TransportException;
use Symfony\Component\Notifier\Channel\ChannelInterface;
use Symfony\Component\Notifier\Transport\Dsn;

final class NotificationsConfig extends InjectableConfig
{
    public const CONFIG = 'notifications';

    protected array $config = [
        'queueConnection' => null,
        'channels' => [],
        'transports' => [],
        'typeAliases' => [],
    ];

    public function getQueueConnection(): ?string
    {
        return $this->config['queueConnection'] ?? null;
    }

    public function getChannelPolicies(): array
    {
        return (array)($this->config['policies'] ?? []);
    }

    /**
     * @param string $name
     * @return array{
     *     type: class-string<ChannelInterface>,
     *     transport: Dsn[]
     * }
     * @throws InvalidArgumentException
     * @throws TransportException
     *
     * @psalm-suppress LessSpecificReturnStatement
     * @psalm-suppress MoreSpecificReturnType
     */
    public function getChannel(string $name): array
    {
        if (! isset($this->config['channels'][$name])) {
            throw new TransportException(
                \sprintf('Channel with given name `%s` is not found.', $name)
            );
        }

        $channel = $this->config['channels'][$name];

        if (! \is_array($channel)) {
            throw new InvalidArgumentException(
                \sprintf('Config for channel `%s` must be an array.', $name)
            );
        }

        if (! isset($channel['type'])) {
            throw new InvalidArgumentException(
                \sprintf('Config for channel `%s` should contain `type` key.', $name)
            );
        }

        if (! isset($channel['transport'])) {
            throw new InvalidArgumentException(
                \sprintf('Config for channel `%s` should contain `transport` key.', $name)
            );
        }

        if (isset($this->config['typeAliases'][$channel['type']])) {
            $channel['type'] = $this->config['typeAliases'][$channel['type']];
        }

        $channel['transport'] = $this->getTransport((array)$channel['transport']);

        return $channel;
    }

    /**
     * @return Dsn[]
     * @throws InvalidArgumentException
     * @throws TransportException
     */
    public function getTransport(array $names): array
    {
        $dsns = [];

        foreach ($names as $name) {
            if (! isset($this->config['transports'][$name])) {
                throw new TransportException(
                    \sprintf('Transport with given name `%s` is not found.', $name)
                );
            }

            $transport = $this->config['transports'][$name];

            if (! \is_string($transport)) {
                throw new InvalidArgumentException(
                    \sprintf('Config for transport `%s` must be a DSN string', $name)
                );
            }

            $dsns[] = new Dsn($transport);
        }


        return $dsns;
    }
}
