<div align="center">
    <h1>Interact SMS</h1>
</div>

<p align="center">
    Client Laravel pour l'<a href="https://developers.phonovation.com/interactsms-api">API Interact SMS</a> (Phonovation) : campagnes SMS, sender IDs, accusés de livraison et SMS entrants.
</p>

<p align="center">
    <a href="https://packagist.org/packages/aristide/interact-sms"><img src="https://img.shields.io/packagist/v/aristide/interact-sms.svg?style=flat-square" alt="Packagist"></a>
    <a href="https://packagist.org/packages/aristide/interact-sms"><img src="https://img.shields.io/packagist/php-v/aristide/interact-sms.svg?style=flat-square" alt="PHP from Packagist"></a>
    <a href="https://badge.laravel.cloud/badge/aristide/interact-sms?style=flat"><img src="https://badge.laravel.cloud/badge/aristide/interact-sms?style=flat" alt="Laravel versions"></a>
    <a href="https://github.com/Aristide-Dev/INTERACT-SMS/actions"><img alt="GitHub Workflow Status (master)" src="https://img.shields.io/github/actions/workflow/status/Aristide-Dev/INTERACT-SMS/tests.yml?branch=master&label=Tests&style=flat-square"></a>
    <a href="https://packagist.org/packages/aristide/interact-sms"><img src="https://img.shields.io/packagist/dt/aristide/interact-sms.svg?style=flat-square" alt="Total Downloads"></a>
</p>

## Prérequis

- PHP 8.3+
- Laravel 10.48, 11, 12 ou 13
- Un compte [Interact SMS / Phonovation](https://secure.interactsms.com/Register.aspx) (nom d'utilisateur et mot de passe API)

## Installation

```bash
composer require aristide/interact-sms
```

Publiez le fichier de configuration :

```bash
php artisan vendor:publish --tag="interact-sms-config"
```

Ajoutez vos identifiants dans `.env` :

```env
INTERACT_SMS_USERNAME=votre-utilisateur
INTERACT_SMS_PASSWORD=votre-mot-de-passe
INTERACT_SMS_SENDER_NAME=VotreSender
INTERACT_SMS_CLIENT_SECRET=votre-secret-de-verification
```

L'authentification suit la spec v2 : `POST https://auth.interactsms.com/token` (`grant_type=password`, `client_id=ismstoken`), puis `Authorization: Bearer` vers `https://api.interactsms.com/api/v2`. Le jeton expire en 120 secondes. Le client le réutilise jusqu'à échéance et en redemande un sur réponse `401`.

| Clé | Description |
| --- | --- |
| `INTERACT_SMS_BASE_URL` | URL de l'API (`https://api.interactsms.com/api/v2` par défaut) |
| `INTERACT_SMS_AUTH_URL` | URL du jeton (`https://auth.interactsms.com/token` par défaut) |
| `INTERACT_SMS_HEALTH_URL` | Health check (`https://api.interactsms.com/health` par défaut) |
| `INTERACT_SMS_USERNAME` | Nom d'utilisateur API |
| `INTERACT_SMS_PASSWORD` | Mot de passe API |
| `INTERACT_SMS_CLIENT_ID` | Client OAuth (`ismstoken` par défaut) |
| `INTERACT_SMS_SENDER_NAME` | Sender ID par défaut, déjà approuvé sur le compte |
| `INTERACT_SMS_CLIENT_SECRET` | Secret utilisé pour vérifier les DLR et les SMS entrants |
| `INTERACT_SMS_TIMEOUT` | Timeout HTTP en secondes (`20` par défaut) |

Il n'y a pas d'environnement de test : chaque envoi réel consomme des crédits.

## Usage

Les réponses JSON sont renvoyées sous forme de tableaux PHP. Les erreurs HTTP lèvent une exception typée. Une campagne partiellement invalide reste en HTTP 200 : les numéros refusés sont dans `errors`.

```php
use InteractSms\InteractSms\Exceptions\InteractSmsException;
use InteractSms\InteractSms\Facades\InteractSms;

try {
    $response = InteractSms::sendSms('353861234567', 'Bonjour depuis Interact SMS');
} catch (InteractSmsException $exception) {
    report($exception);
}
```

### Campagne SMS

`POST /api/v2/Campaign`. Le champ `from` doit être un Sender ID déjà approuvé.

```php
InteractSms::sendSms('353861234567', 'Bonjour depuis Interact SMS');

InteractSms::sendSms(
    ['353861234567', '353871234567'],
    'Votre commande est en route',
    campaignName: 'Order Notifications',
    notifyId: ['order-001', 'order-002'],
);
```

`notifyId` (30 caractères maximum) est renvoyé dans les webhooks, les accusés de livraison et, quand le lien est possible, les réponses entrantes. Un seul `notifyId` ne peut pas être partagé entre plusieurs destinataires.

`sendAt` est une heure irlandaise (`Europe/Dublin`), au format `yyyy-MM-ddTHH:mm`, sans conversion UTC :

```php
InteractSms::sendSms('0871234567', 'Rappel', sendAt: '2030-06-01T09:00');
```

Les numéros irlandais sont acceptés avec l'indicatif `353` ou un zéro initial (`087…`). Les autres numéros doivent inclure l'indicatif pays. Une requête accepte jusqu'à 400 000 destinataires. Le texte est limité à 2 000 caractères ; au-delà de 4 parties GSM, le message n'est pas pris en charge par la plateforme.

Le payload brut reste disponible :

```php
InteractSms::campaigns()->send([
    'text' => 'Bonjour',
    'from' => 'VotreSender',
    'recipientInfo' => [
        ['msisdn' => '353861234567', 'notifyId' => 'order-001'],
    ],
]);
```

### Sender IDs

`GET /api/v2/senderlist`

| Type | Signification |
| --- | --- |
| `0` | Sender ID ComReg (Irlande) |
| `1` | Sender ID international (ne livre pas vers l'Irlande) |
| `2` | Sender ID numérique |

```php
InteractSms::senderNames()->list();
```

### Disponibilité

`GET /health` ne demande pas de jeton.

```php
InteractSms::health();
```

### Webhooks

L'URL se configure auprès de Phonovation (`support@phonovation.com`). Répondez `200 OK`.

Le webhook de traitement (JSON, un payload par destinataire) confirme que le message a été accepté. L'accusé de livraison (formulaire) confirme le réseau mobile. Le SMS entrant (formulaire) porte les réponses et les opt-out (`SMS-To=50123`).

```php
use Illuminate\Http\Request;
use InteractSms\InteractSms\Facades\InteractSms;

Route::post('/webhooks/interact-sms', function (Request $request) {
    $payload = InteractSms::parseWebhook($request->all());

    return response()->json(['status' => 'OK']);
});
```

Vérification des accusés : `MD5(SMS-NotifyId + SMS-Success + client secret)`.

Vérification des SMS entrants : `MD5(SMS-From + SMS-Content + client secret)`.

```php
$receipt = InteractSms::webhooks()->parseDeliveryReceipt($request->all());

if (! InteractSms::webhooks()->verifyDeliveryReceipt($receipt)) {
    abort(400);
}

$receipt->delivered();
$receipt->notifyId;
```

```php
$message = InteractSms::webhooks()->parseInboundMessage($request->all());

if ($message->isOptOut()) {
    // Ajouter $message->from à la liste de suppression.
}
```

Statuts du webhook de traitement : `MESSAGE_SUCCESS`, `MESSAGE_ERROR`, `CAMPAIGN_ERROR`.

## Exceptions

| HTTP | Exception |
| --- | --- |
| 400 / 415 / 422 | `InteractSms\InteractSms\Exceptions\ValidationException` |
| 401 | `AuthenticationException` |
| 404 | `NotFoundException` |
| 429 | `RateLimitException` |
| 5xx | `ServerException` |
| autre | `InteractSmsException` |

Toutes étendent `InteractSmsException` (`$exception->status`, `$exception->body`).

## Tests dans l'application hôte

```php
use Illuminate\Support\Facades\Http;
use InteractSms\InteractSms\Facades\InteractSms;

Http::fake([
    'https://auth.interactsms.com/token' => Http::response([
        'access_token' => 'token-de-test',
        'expires_in' => 120,
        'token_type' => 'Bearer',
    ], 200),
    'https://api.interactsms.com/api/v2/Campaign' => Http::response([
        'success' => true,
        'message' => 'Campaign scheduled',
        'errors' => [],
    ], 200),
]);

InteractSms::sendSms('353861234567', 'Test');

Http::assertSentCount(2);
```

## Documentation officielle

- [developers.phonovation.com](https://developers.phonovation.com/interactsms-api)
- OpenAPI : `https://developers.phonovation.com/openapi/oauth.yaml`

## Changelog

Voir [CHANGELOG](CHANGELOG.md).

## Contributing

Voir [.github/CONTRIBUTING.md](.github/CONTRIBUTING.md).

## Security Vulnerabilities

Voir [.github/SECURITY.md](.github/SECURITY.md).

## Credits

- [Aristide](https://github.com/Aristide-Dev)
- [All Contributors](../../contributors)

## License

Interact SMS is open-sourced software licensed under the [MIT license](LICENSE.md).
