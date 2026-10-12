<?php

declare(strict_types=1);

namespace InteractSms\InteractSms\Tests;

use InteractSms\InteractSms\InteractSmsServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            InteractSmsServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('interact-sms.username', 'user');
        $app['config']->set('interact-sms.password', 'secret');
        $app['config']->set('interact-sms.sender_name', 'Phonovation');
        $app['config']->set('interact-sms.client_secret', 'client-secret');
        $app['config']->set('interact-sms.timeout', 20);
    }
}
