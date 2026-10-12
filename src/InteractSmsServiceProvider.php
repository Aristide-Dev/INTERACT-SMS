<?php

declare(strict_types=1);

namespace InteractSms\InteractSms;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use InteractSms\InteractSms\Http\Client;

class InteractSmsServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/interact-sms.php', 'interact-sms');

        $this->app->singleton(Client::class, function (Application $app): Client {
            $config = $app->make(Repository::class);

            return new Client(
                baseUrl: rtrim((string) $config->get('interact-sms.base_url'), '/'),
                authUrl: (string) $config->get('interact-sms.auth_url'),
                healthUrl: (string) $config->get('interact-sms.health_url'),
                username: (string) $config->get('interact-sms.username', ''),
                password: (string) $config->get('interact-sms.password', ''),
                clientId: (string) $config->get('interact-sms.client_id', 'ismstoken'),
                timeout: (int) $config->get('interact-sms.timeout', 20),
                senderName: (string) $config->get('interact-sms.sender_name', ''),
                clientSecret: (string) $config->get('interact-sms.client_secret', ''),
            );
        });

        $this->app->singleton(InteractSms::class, function (Application $app): InteractSms {
            return new InteractSms($app->make(Client::class));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../config/interact-sms.php' => config_path('interact-sms.php'),
        ], ['interact-sms', 'interact-sms-config']);
    }
}
