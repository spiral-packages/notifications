<?php

namespace Spiral\Notifications\Tests;

use Spiral\Boot\Bootloader\ConfigurationBootloader;
use Spiral\Notifications\Bootloader\NotificationsBootloader;
use Symfony\Component\Notifier\NotifierInterface;

abstract class TestCase extends \Spiral\Testing\TestCase
{
    public function getNotifier(): NotifierInterface
    {
        return $this->getContainer()->get(NotifierInterface::class);
    }

    public function rootDirectory(): string
    {
        return __DIR__.'/../';
    }

    public function defineBootloaders(): array
    {
        return [
            ConfigurationBootloader::class,
            NotificationsBootloader::class,
        ];
    }
}
