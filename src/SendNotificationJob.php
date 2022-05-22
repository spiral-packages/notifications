<?php

declare(strict_types=1);

namespace Spiral\Notifications;

use Spiral\Queue\Exception\InvalidArgumentException;
use Spiral\Queue\HandlerInterface;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Notifier\Recipient\RecipientInterface;

final class SendNotificationJob implements HandlerInterface
{
    public function __construct(
        private NotifierInterface $notifier
    ) {
    }

    /**
     * @param array{
     *     notification: Notification,
     *     recipient: RecipientInterface
     * } $payload
     * @throws InvalidArgumentException
     */
    public function handle(string $name, string $id, array $payload): void
    {
        if (! isset($payload['notification'])) {
            throw new InvalidArgumentException('Payload `notification` key is required.');
        }

        if (! $payload['notification'] instanceof Notification) {
            throw new InvalidArgumentException(
                sprintf(
                    'Payload `notification` key value type should be instance of `%s`',
                    Notification::class
                )
            );
        }

        if (! isset($payload['recipient'])) {
            throw new InvalidArgumentException('Payload `recipient` key is required.');
        }


        if (! $payload['recipient'] instanceof RecipientInterface) {
            throw new InvalidArgumentException(
                sprintf(
                    'Payload `recipient` key value type should be instance of `%s`',
                    RecipientInterface::class
                )
            );
        }

        $notification = $payload['notification'];
        $recipient = $payload['recipient'];

        $this->notifier->sendNow($notification, $recipient);
    }
}
