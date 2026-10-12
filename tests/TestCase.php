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
}
