<?php

declare(strict_types=1);

namespace InteractSms\InteractSms\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \InteractSms\InteractSms\InteractSms
 */
class InteractSms extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \InteractSms\InteractSms\InteractSms::class;
    }
}
