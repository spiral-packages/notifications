<?php

declare(strict_types=1);

namespace Spiral\Notifications\Tests\App\Users;

use Symfony\Component\Notifier\Recipient\RecipientInterface;
use Symfony\Component\Notifier\Recipient\SmsRecipientInterface;

class UserWithPhone implements RecipientInterface, SmsRecipientInterface
{
    public function getPhone(): string
    {
        return '+8(000)000-00-00';
    }
}
