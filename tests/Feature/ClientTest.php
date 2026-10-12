<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use InteractSms\InteractSms\Data\DeliveryReceipt;
use InteractSms\InteractSms\Data\InboundMessage;
use InteractSms\InteractSms\Data\ProcessingResult;
use InteractSms\InteractSms\Exceptions\AuthenticationException;
use InteractSms\InteractSms\Exceptions\NotFoundException;
use InteractSms\InteractSms\Exceptions\RateLimitException;
use InteractSms\InteractSms\Exceptions\ServerException;
use InteractSms\InteractSms\Exceptions\ValidationException;
use InteractSms\InteractSms\Facades\InteractSms;
use InteractSms\InteractSms\Http\Client;
use InteractSms\InteractSms\InteractSms as InteractSmsClient;
use InteractSms\InteractSms\Resources\Campaigns;

beforeEach(function () {
    Http::preventStrayRequests();
});

it('resolves the singleton', function () {
    expect(app(InteractSmsClient::class))->toBeInstanceOf(InteractSmsClient::class);
    expect(app(InteractSmsClient::class))->toBe(app(InteractSmsClient::class));
});

it('merges the package config', function () {
    expect(config('interact-sms.base_url'))->toBe('https://api.interactsms.com/api/v2')
        ->and(config('interact-sms.auth_url'))->toBe('https://auth.interactsms.com/token')
        ->and(config('interact-sms.client_id'))->toBe('ismstoken')
        ->and(config('interact-sms.sender_name'))->toBe('Phonovation');
});

it('publishes the configuration file', function () {
    $this->artisan('vendor:publish', ['--tag' => 'interact-sms-config'])
        ->assertSuccessful();

    expect(file_exists(config_path('interact-sms.php')))->toBeTrue();
});

it('sends an sms campaign with a bearer token', function () {
    fakeInteract();

    $response = InteractSms::sendSms('353861234567', 'Hello from Interact SMS');

    expect($response)->toHaveKey('success', true);

    Http::assertSent(function ($request) {
        if ($request->url() !== 'https://api.interactsms.com/api/v2/Campaign') {
            return false;
        }

        $body = $request->data();

        return $request->method() === 'POST'
            && $request->hasHeader('Authorization', 'Bearer token-1')
            && $body['text'] === 'Hello from Interact SMS'
            && $body['from'] === 'Phonovation'
            && $body['recipientInfo'] === [
                ['msisdn' => '353861234567'],
            ];
    });
});

it('sends to several recipients with notify ids and a schedule', function () {
    fakeInteract();

    InteractSms::sendSms(
        ['353861234567', '353871234567'],
        'Your order is on the way',
        campaignName: 'Order Notifications',
        sendAt: '2030-06-01T09:00',
        notifyId: ['order-001', 'order-002'],
    );

    Http::assertSent(function ($request) {
        if ($request->url() !== 'https://api.interactsms.com/api/v2/Campaign') {
            return false;
        }

        $body = $request->data();

        return $body['campaignName'] === 'Order Notifications'
            && $body['sendAt'] === '2030-06-01T09:00'
            && $body['recipientInfo'][0]['notifyId'] === 'order-001'
            && $body['recipientInfo'][1]['notifyId'] === 'order-002';
    });
});

it('returns partial campaign errors without throwing', function () {
    fakeInteract([
        'success' => true,
        'message' => 'Campaign scheduled',
        'errors' => ['353000000000'],
    ]);

    $response = InteractSms::sendSms(
        [
            ['msisdn' => '353861234567', 'notifyId' => 'order-001'],
            ['msisdn' => '353000000000'],
        ],
        'Hello',
    );

    expect($response['errors'])->toBe(['353000000000']);
});

it('reuses the access token until it expires', function () {
    Http::fake([
        'https://auth.interactsms.com/token' => Http::response(tokenPayload(), 200),
        'https://api.interactsms.com/api/v2/senderlist' => Http::response([
            ['name' => 'Phonovation', 'type' => 0],
            ['name' => '353851234567', 'type' => 2],
        ], 200),
    ]);

    InteractSms::senderNames()->list();
    $senders = InteractSms::senderNames()->list();

    expect($senders[0]['name'])->toBe('Phonovation');

    $tokenRequests = Http::recorded(fn ($request): bool => $request->url() === 'https://auth.interactsms.com/token');

    expect($tokenRequests)->toHaveCount(1);
});

it('refreshes the token once after an unauthorized response', function () {
    Http::fake([
        'https://auth.interactsms.com/token' => Http::sequence()
            ->push(tokenPayload('token-1'), 200)
            ->push(tokenPayload('token-2'), 200),
        'https://api.interactsms.com/api/v2/Campaign' => Http::sequence()
            ->push(['success' => false, 'message' => 'Unauthorized', 'errors' => ['401 Unauthorized']], 401)
            ->push(['success' => true, 'message' => 'Campaign scheduled', 'errors' => []], 200),
    ]);

    $response = InteractSms::sendSms('353861234567', 'Hello');

    expect($response)->toHaveKey('success', true);

    Http::assertSent(fn ($request): bool => $request->hasHeader('Authorization', 'Bearer token-2'));
});

it('checks the service health without a token', function () {
    Http::fake([
        'https://api.interactsms.com/health' => Http::response('', 200),
    ]);

    expect(InteractSms::health())->toBe(['status' => 'ok']);

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api.interactsms.com/health'
        && ! $request->hasHeader('Authorization'));
});

it('maps api errors to typed exceptions', function (int $status, string $exception) {
    Http::fake([
        'https://auth.interactsms.com/token' => Http::response(tokenPayload(), 200),
        'https://api.interactsms.com/api/v2/senderlist' => Http::response([
            'message' => 'Erreur',
            'errors' => ['Erreur'],
        ], $status),
    ]);

    expect(fn () => InteractSms::senderNames()->list())->toThrow($exception, 'Erreur');
})->with([
    [400, ValidationException::class],
    [401, AuthenticationException::class],
    [404, NotFoundException::class],
    [429, RateLimitException::class],
    [500, ServerException::class],
]);

it('throws when credentials are rejected', function () {
    Http::fake([
        'https://auth.interactsms.com/token' => Http::response([
            'error' => 'invalid_grant',
            'error_description' => 'Invalid user credentials',
        ], 401),
    ]);

    expect(fn () => InteractSms::sendSms('353861234567', 'Hello'))
        ->toThrow(AuthenticationException::class, 'Invalid user credentials');
});

it('rejects an empty message, a missing sender, and too many recipients', function () {
    expect(fn () => InteractSms::sendSms('353861234567', '   '))
        ->toThrow(InvalidArgumentException::class, 'Message text cannot be empty.');

    config(['interact-sms.sender_name' => '']);
    $this->app->forgetInstance(Client::class);
    $this->app->forgetInstance(InteractSmsClient::class);
    InteractSms::clearResolvedInstances();

    expect(fn () => InteractSms::sendSms('353861234567', 'Hello'))
        ->toThrow(InvalidArgumentException::class, 'A sender name is required.');

    config(['interact-sms.sender_name' => 'Phonovation']);
    $this->app->forgetInstance(Client::class);
    $this->app->forgetInstance(InteractSmsClient::class);
    InteractSms::clearResolvedInstances();

    expect(fn () => InteractSms::sendSms(array_fill(0, Campaigns::MAX_RECIPIENTS + 1, '353861234567'), 'Hello'))
        ->toThrow(InvalidArgumentException::class, 'A campaign accepts at most 400000 recipients.');
});

it('parses processing results, delivery receipts, and inbound messages', function () {
    $secret = 'client-secret';
    $dlrVerify = hash('md5', 'order-001True'.$secret);
    $inboundVerify = hash('md5', '353861234567Test reply'.$secret);

    $processing = InteractSms::parseWebhook([
        'Status' => 'MESSAGE_SUCCESS',
        'FinishedAt' => '2026-04-09T14:39:40.69257Z',
        'Message' => 'Your message has been queued.',
        'NotifyId' => 'order-001',
    ]);

    $receipt = InteractSms::parseWebhook([
        'SMS-Type' => 'Notification',
        'SMS-NotifyId' => 'order-001',
        'SMS-Success' => 'True',
        'SMS-To' => '353861234567',
        'SMS-From' => 'Phonovation',
        'SMS-Verify' => $dlrVerify,
        'SMS-TotalToSend' => 1,
        'SMS-TotalSent' => 1,
        'SMS-TotalFailed' => 0,
        'SMS-TimeStamp' => '2026-04-09 16:03:16',
    ]);

    $inbound = InteractSms::parseWebhook([
        'SMS-Type' => 'AuthCode',
        'SMS-Content' => 'Test reply',
        'SMS-Keyphrase' => '*',
        'SMS-From' => '353861234567',
        'SMS-To' => '353871234568',
        'SMS-NotifyId' => 'order-001',
        'SMS-Verify' => $inboundVerify,
        'SMS-TimeStamp' => '2026-04-09 16:03:11',
    ]);

    $optOut = InteractSms::webhooks()->parseInboundMessage([
        'SMS-Content' => 'Optout',
        'SMS-From' => '353861234567',
        'SMS-To' => '50123',
    ]);

    expect($processing)->toBeInstanceOf(ProcessingResult::class)
        ->and($processing->accepted())->toBeTrue()
        ->and($processing->notifyId)->toBe('order-001')
        ->and($receipt)->toBeInstanceOf(DeliveryReceipt::class)
        ->and($receipt->delivered())->toBeTrue()
        ->and(InteractSms::webhooks()->verifyDeliveryReceipt($receipt))->toBeTrue()
        ->and($inbound)->toBeInstanceOf(InboundMessage::class)
        ->and(InteractSms::webhooks()->verifyInboundMessage($inbound))->toBeTrue()
        ->and($optOut->isOptOut())->toBeTrue();
});

it('rejects a webhook payload it cannot recognize', function () {
    expect(fn () => InteractSms::parseWebhook(['foo' => 'bar']))
        ->toThrow(InvalidArgumentException::class);
});

/**
 * @param  array<string, mixed>|null  $campaign
 */
function fakeInteract(?array $campaign = null): void
{
    Http::fake([
        'https://auth.interactsms.com/token' => Http::response(tokenPayload(), 200),
        'https://api.interactsms.com/api/v2/Campaign' => Http::response($campaign ?? [
            'success' => true,
            'message' => 'Campaign scheduled',
            'errors' => [],
        ], 200),
    ]);
}

/**
 * @return array{access_token: string, expires_in: int, token_type: string}
 */
function tokenPayload(string $token = 'token-1'): array
{
    return [
        'access_token' => $token,
        'expires_in' => 120,
        'token_type' => 'Bearer',
    ];
}
