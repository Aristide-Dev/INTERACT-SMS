<?php

declare(strict_types=1);

return [

    'base_url' => env('INTERACT_SMS_BASE_URL', 'https://api.interactsms.com/api/v2'),

    'auth_url' => env('INTERACT_SMS_AUTH_URL', 'https://auth.interactsms.com/token'),

    'health_url' => env('INTERACT_SMS_HEALTH_URL', 'https://api.interactsms.com/health'),

    'username' => env('INTERACT_SMS_USERNAME'),

    'password' => env('INTERACT_SMS_PASSWORD'),

    'client_id' => env('INTERACT_SMS_CLIENT_ID', 'ismstoken'),

    'sender_name' => env('INTERACT_SMS_SENDER_NAME'),

    'client_secret' => env('INTERACT_SMS_CLIENT_SECRET'),

    'timeout' => env('INTERACT_SMS_TIMEOUT', 20),

];
