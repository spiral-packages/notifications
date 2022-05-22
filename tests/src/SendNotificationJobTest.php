<?php

declare(strict_types=1);

namespace Spiral\Notifications\Tests;

use Mockery as m;
use Spiral\Notifications\SendNotificationJob;
use Spiral\Queue\Exception\InvalidArgumentException;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Notifier\Recipient\RecipientInterface;

final class SendNotificationJobTest extends TestCase
{
    private SendNotificationJob $job;
    private m\LegacyMockInterface|NotifierInterface|m\MockInterface $notifier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->job = new SendNotificationJob(
            $this->notifier = m::mock(NotifierInterface::class)
        );
    }

    public function testPayloadWithoutNotificationKeyShouldThrowAnException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectErrorMessage('Payload `notification` key is required.');

        $this->job->handle('foo', 'bar', []);
    }

    public function testInvalidPayloadNotificationKeyShouldThrowAnException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectErrorMessage(
            'Payload `notification` key value type should be instance of `Symfony\Component\Notifier\Notification\Notification`'
        );

        $this->job->handle('foo', 'bar', [
            'notification' => 'foo',
        ]);
    }

    public function testPayloadWithoutRecipientKeyShouldThrowAnException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectErrorMessage('Payload `recipient` key is required.');

        $this->job->handle('foo', 'bar', [
            'notification' => m::mock(Notification::class),
        ]);
    }

    public function testInvalidPayloadRecipientKeyShouldThrowAnException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectErrorMessage(
            'Payload `recipient` key value type should be instance of `Symfony\Component\Notifier\Recipient\RecipientInterface`'
        );

        $this->job->handle('foo', 'bar', [
            'notification' => m::mock(Notification::class),
            'recipient' => 'foo',
        ]);
    }

    public function testNotificationShouldBeSent(): void
    {
        $notification = m::mock(Notification::class);
        $recipient = m::mock(RecipientInterface::class);

        $this->notifier->shouldReceive('sendNow')->once()->with($notification, $recipient);

        $this->job->handle('foo', 'bar', [
            'notification' => $notification,
            'recipient' => $recipient,
        ]);
    }
}
