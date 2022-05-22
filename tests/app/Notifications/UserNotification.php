<?php

declare(strict_types=1);

namespace Spiral\Notifications\Tests\App\Notifications;

use Spiral\Queue\QueueableInterface;
use Symfony\Component\Notifier\Message\EmailMessage;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\Recipient\EmailRecipientInterface;
use Symfony\Component\Notifier\Recipient\RecipientInterface;

class UserNotification extends Notification implements QueueableInterface
{
    public function getChannels(RecipientInterface $recipient): array
    {
        return ['email'];
    }

    public function asEmailMessage(EmailRecipientInterface $recipient, string $transport = null): ?EmailMessage
    {
        return EmailMessage::fromNotification($this, $recipient);
    }
}
