<?php

namespace Spiral\Notifications;

use Spiral\Notifications\Config\NotificationsConfig;
use Spiral\Queue\QueueableInterface;
use Spiral\Queue\QueueConnectionProviderInterface;
use Symfony\Component\Notifier\Channel\ChannelInterface;
use Symfony\Component\Notifier\Channel\ChannelPolicy;
use Symfony\Component\Notifier\Channel\ChannelPolicyInterface;
use Symfony\Component\Notifier\Channel\SmsChannel;
use Symfony\Component\Notifier\Exception\LogicException;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Notifier\Recipient\NoRecipient;
use Symfony\Component\Notifier\Recipient\RecipientInterface;

final class Notifier implements NotifierInterface
{
    private array $adminRecipients = [];

    public function __construct(
        private readonly ChannelManager $channelManager,
        private readonly NotificationsConfig $config,
        private readonly QueueConnectionProviderInterface $queue,
        private readonly ?ChannelPolicyInterface $policy = null
    ) {
    }

    public function sendQueued(Notification $notification, RecipientInterface ...$recipients): void
    {
        if (! $recipients) {
            $recipients = [new NoRecipient()];
        }

        $queue = $this->queue->getConnection($this->config->getQueueConnection());

        foreach ($recipients as $recipient) {
            foreach ($this->getChannels($notification, $recipient) as $channel => $transportName) {
                $queue->push(SendNotificationJob::class, [
                    'notification' => $notification,
                    'recipient' => $recipient,
                    'transportName' => $transportName,
                ]);
            }
        }
    }

    public function send(Notification $notification, RecipientInterface ...$recipients): void
    {
        if ($notification instanceof QueueableInterface) {
            $this->sendQueued($notification, ...$recipients);

            return;
        }

        $this->sendNow($notification, ...$recipients);
    }

    public function sendNow(Notification $notification, RecipientInterface ...$recipients): void
    {
        if (! $recipients) {
            $recipients = [new NoRecipient()];
        }

        foreach ($recipients as $recipient) {
            foreach ($this->getChannels($notification, $recipient) as $channel => $transportName) {
                $channel->notify($notification, $recipient, $transportName);
            }
        }
    }

    public function addAdminRecipient(RecipientInterface $recipient): void
    {
        $this->adminRecipients[] = $recipient;
    }

    /** @return RecipientInterface[] */
    public function getAdminRecipients(): array
    {
        return $this->adminRecipients;
    }

    /**
     * @return \Generator<ChannelInterface, null|string, mixed, void>
     */
    private function getChannels(Notification $notification, RecipientInterface $recipient): iterable
    {
        $channels = $notification->getChannels($recipient);

        if (! $channels) {
            $errorPrefix = \sprintf(
                'Unable to determine which channels to use to send the "%s" notification',
                \get_class($notification)
            );

            $error = 'you should either pass channels in the constructor, override its "getChannels()" method';
            if (null === $this->policy) {
                throw new LogicException(
                    \sprintf('%s; %s, or configure a "%s".', $errorPrefix, $error, ChannelPolicy::class)
                );
            }

            if (! $channels = $this->policy->getChannels($notification->getImportance())) {
                throw new LogicException(
                    \sprintf(
                        '%s; the "%s" returns no channels for importance "%s"; %s.',
                        $errorPrefix,
                        ChannelPolicy::class,
                        $notification->getImportance(),
                        $error
                    )
                );
            }
        }

        foreach ($channels as $channelName) {
            $transportName = null;
            if (false !== $pos = strpos($channelName, '/')) {
                $transportName = substr($channelName, $pos + 1);
                $channelName = substr($channelName, 0, $pos);
            }

            if (null === $channel = $this->getChannel($channelName)) {
                throw new LogicException(sprintf('The "%s" channel does not exist.', $channelName));
            }

            if ($channel instanceof SmsChannel && $recipient instanceof NoRecipient) {
                throw new LogicException(sprintf('The "%s" channel needs a Recipient.', $channelName));
            }

            if (! $channel->supports($notification, $recipient)) {
                throw new LogicException(sprintf('The "%s" channel is not supported.', $channelName));
            }

            yield $channel => $transportName;
        }
    }

    private function getChannel(string $name): ?ChannelInterface
    {
        return $this->channelManager->getChannel($name);
    }
}
