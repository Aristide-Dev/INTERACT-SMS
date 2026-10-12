<?php

declare(strict_types=1);

namespace InteractSms\InteractSms\Facades;

use Illuminate\Support\Facades\Facade;
use InteractSms\InteractSms\Data\DeliveryReceipt;
use InteractSms\InteractSms\Data\InboundMessage;
use InteractSms\InteractSms\Data\ProcessingResult;
use InteractSms\InteractSms\Resources\Campaigns;
use InteractSms\InteractSms\Resources\SenderNames;
use InteractSms\InteractSms\Resources\Webhooks;

/**
 * @method static Campaigns campaigns()
 * @method static array<string, mixed> sendSms(string|array<int, string|array<string, mixed>> $to, string $text, ?string $from = null, ?string $campaignName = null, ?string $sendAt = null, string|array<int, string>|null $notifyId = null)
 * @method static SenderNames senderNames()
 * @method static array<string, mixed>|list<mixed> health()
 * @method static Webhooks webhooks()
 * @method static ProcessingResult|DeliveryReceipt|InboundMessage parseWebhook(array<string, mixed> $payload)
 *
 * @see \InteractSms\InteractSms\InteractSms
 */
class InteractSms extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \InteractSms\InteractSms\InteractSms::class;
    }
}
