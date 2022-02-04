<?php

declare(strict_types=1);

namespace Spiral\Notifications\Tests\App\Users;

use Symfony\Component\Notifier\Recipient\EmailRecipientInterface;
use Symfony\Component\Notifier\Recipient\RecipientInterface;

class UserWithEmail implements RecipientInterface, EmailRecipientInterface
{
    public function getEmail(): string
    {
        return 'user@site.com';
    }
}
