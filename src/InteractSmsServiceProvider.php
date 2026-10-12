<?php

declare(strict_types=1);

namespace InteractSms\InteractSms;

use Illuminate\Support\ServiceProvider;
use InteractSms\InteractSms\Console\Commands\InteractSmsCommand;

class InteractSmsServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/interact-sms.php', 'interact-sms');

        $this->app->singleton(InteractSms::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/interact-sms.php');

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'interact-sms');

        $this->loadTranslationsFrom(__DIR__.'/../lang', 'interact-sms');

        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../config/interact-sms.php' => config_path('interact-sms.php'),
        ], ['interact-sms', 'interact-sms-config']);

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/interact-sms'),
        ], ['interact-sms', 'interact-sms-views']);

        $this->publishes([
            __DIR__.'/../lang' => $this->app->langPath('vendor/interact-sms'),
        ], ['interact-sms', 'interact-sms-lang']);

        $this->publishes([
            __DIR__.'/../public' => public_path('vendor/interact-sms'),
        ], ['interact-sms', 'interact-sms-assets']);

        $this->publishesMigrations([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], ['interact-sms', 'interact-sms-migrations']);

        $this->commands([
            InteractSmsCommand::class,
        ]);
    }
}
