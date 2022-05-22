<?php

declare(strict_types=1);

namespace Spiral\Notifications\Tests;

use Mockery as m;
use Spiral\Notifications\SendNotificationJob;
use Spiral\Notifications\Tests\App\Notifications\UserNotification;
use Spiral\Notifications\Tests\App\Notifications\UserRegisteredNotification;
use Spiral\Notifications\Tests\App\Users\UserWithEmail;
use Spiral\Queue\QueueConnectionProviderInterface;
use Spiral\Queue\QueueInterface;
use Spiral\Testing\Mailer\FakeMailer;
use Symfony\Component\Notifier\Channel\EmailChannel;

final class NotifierTest extends TestCase
{
    private FakeMailer $mailer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mailer = $this->fakeMailer();
    }

    public function testSendEmailNotification()
    {
        $notification = new UserRegisteredNotification();
        $emailRecipient = new UserWithEmail();

        $channel = $this->mockContainer(EmailChannel::class);
        $channel->shouldReceive('supports')->once()->with($notification, $emailRecipient)->andReturnTrue();
        $channel->shouldReceive('notify')->once()->with($notification, $emailRecipient, null);

        $this->getNotifier()->send($notification, $emailRecipient);
    }

    public function testSendEmailNotificationViaQueue()
    {
        $this->mockContainer(QueueConnectionProviderInterface::class)
            ->shouldReceive('getConnection')
            ->once()
            ->andReturn($queue = m::mock(QueueInterface::class));


        $notification = new UserNotification();
        $emailRecipient = new UserWithEmail();

        $queue->shouldReceive('push')->once()->with(SendNotificationJob::class, [
            'notification' => $notification,
            'recipient' => $emailRecipient,
            'transportName' => null,
        ]);

        $this->getNotifier()->send($notification, $emailRecipient);
    }
}
