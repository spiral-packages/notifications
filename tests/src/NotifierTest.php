<?php

declare(strict_types=1);

namespace Spiral\Notifications\Tests;

use Spiral\Notifications\Tests\App\Notifications\UserRegisteredNotification;
use Spiral\Notifications\Tests\App\Users\UserWithEmail;
use Spiral\Notifications\Tests\App\Users\UserWithPhone;
use Symfony\Component\Notifier\Channel\EmailChannel;

final class NotifierTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->mailer = $this->fakeMailer();
    }

    public function testSendEmailNotificationViaQueue()
    {
        $notification = new UserRegisteredNotification();
        $emailRecipient = new UserWithEmail();

        $channel = $this->mockContainer(EmailChannel::class);
        $channel->shouldReceive('supports')->once()->with($notification, $emailRecipient)->andReturnTrue();
        $channel->shouldReceive('notify')->once()->with($notification, $emailRecipient, null);

        $this->getNotifier()->send($notification, $emailRecipient);
    }
}
