---
name: interact-sms-development
description: >
  Configure and apply the aristide/interact-sms Laravel client for the Interact SMS
  API (Phonovation): SMS campaigns, sender IDs, delivery receipts, and inbound messages.
license: MIT
metadata:
  author: Aristide
---

# Interact SMS

Use this skill when a Laravel application needs to send SMS campaigns or parse Interact SMS webhooks through `aristide/interact-sms`.

## Primary Goal

- apply the `aristide/interact-sms` public API in the smallest correct way

## Workflow

### 1. Inspect the Laravel app context

- confirm the app is a Laravel 10.48, 11, 12, or 13 project
- check whether `INTERACT_SMS_USERNAME`, `INTERACT_SMS_PASSWORD`, and `INTERACT_SMS_SENDER_NAME` are already set
- prefer the facade `InteractSms\InteractSms\Facades\InteractSms` at call sites

### 2. Install and configure

```bash
composer require aristide/interact-sms
php artisan vendor:publish --tag="interact-sms-config"
```

Set at least:

```env
INTERACT_SMS_USERNAME=
INTERACT_SMS_PASSWORD=
INTERACT_SMS_SENDER_NAME=
INTERACT_SMS_CLIENT_SECRET=
```

There is no sandbox. Live sends spend credits. The client obtains an OAuth password-grant token (`client_id=ismstoken`) and refreshes it on expiry or HTTP 401.

### 3. Apply the package's public API

- SMS: `InteractSms::sendSms($to, $text)` — `from` defaults to `INTERACT_SMS_SENDER_NAME` and must already be approved
- Bulk: pass a list of numbers and a list of `notifyId` values (max 30 characters, one per recipient)
- Schedule: `sendAt: '2030-06-01T09:00'` is Irish local time (`Europe/Dublin`), not UTC
- Sender IDs: `InteractSms::senderNames()->list()` — type `0` ComReg, `1` international, `2` numeric
- Health: `InteractSms::health()`
- Inbound webhook: `InteractSms::parseWebhook($request->all())` then return HTTP 200
- Verify a delivery receipt with `InteractSms::webhooks()->verifyDeliveryReceipt(...)` and an inbound message with `verifyInboundMessage(...)`
- Opt-out: `InboundMessage::isOptOut()` when `SMS-To` is `50123`
- Errors: catch `InteractSms\InteractSms\Exceptions\InteractSmsException` (or the 401/404/429 subclasses). HTTP 200 with a non-empty `errors` array is a partial accept, not an exception

### 4. Tests in the host app

Fake `Illuminate\Support\Facades\Http` for both `https://auth.interactsms.com/token` and `https://api.interactsms.com/api/v2/...`. Do not hit the live API in tests.

## Rules, References, and Templates

Read before executing:

- `config/interact-sms.php` keys after publish
- Official docs: https://developers.phonovation.com/interactsms-api
- OpenAPI: https://developers.phonovation.com/openapi/oauth.yaml

## Examples

Send an SMS from a Laravel action:

```php
use InteractSms\InteractSms\Facades\InteractSms;

InteractSms::sendSms($user->phone, 'Votre commande est confirmée.', notifyId: 'order-'.$order->id);
```

Verify a delivery receipt:

```php
$receipt = InteractSms::webhooks()->parseDeliveryReceipt($request->all());

if (! InteractSms::webhooks()->verifyDeliveryReceipt($receipt) || ! $receipt->delivered()) {
    return response()->noContent();
}
```

## Anti-patterns

- do not call the legacy `sendmessage.aspx` endpoint; this client uses API v2
- do not treat `sendAt` as UTC; convert the clock time to `Europe/Dublin` first
- do not share one `notifyId` across several recipients
- do not call `env()` in application code; read `config('interact-sms.*')`
- do not swallow API errors as `['error' => ...]`; exceptions are the contract
- do not skip `200 OK` on webhook endpoints; Phonovation expects that response
