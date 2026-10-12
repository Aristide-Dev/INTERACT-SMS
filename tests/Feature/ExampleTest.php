<?php

declare(strict_types=1);

use InteractSms\InteractSms\InteractSms;

it('resolves the singleton', function () {
    expect(app(InteractSms::class))->toBeInstanceOf(InteractSms::class);
});

it('returns the same instance from the container', function () {
    expect(app(InteractSms::class))->toBe(app(InteractSms::class));
});

it('merges the package config', function () {
    expect(config('interact-sms.placeholder'))->toBe('default');
});

it('loads the package translations', function () {
    expect(trans('interact-sms::messages.placeholder'))->toBe('InteractSms placeholder translation.');
});

it('loads the package views', function () {
    expect(view()->exists('interact-sms::placeholder'))->toBeTrue();
});

it('registers the artisan command', function () {
    $this->artisan('interact-sms:placeholder')
        ->expectsOutputToContain('InteractSms placeholder command executed.')
        ->assertSuccessful();
});
