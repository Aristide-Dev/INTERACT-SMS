<?php

declare(strict_types=1);

use InteractSms\InteractSms\Tests\TestCase;

// pest-plugin-type-coverage forks through Pokio and races on .temp/v3.php.
$_ENV['__PEST_PLUGIN_ENV'] ??= 'testing';

uses(TestCase::class)->in(__DIR__);
