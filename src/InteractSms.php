<?php

declare(strict_types=1);

namespace InteractSms\InteractSms;

use InteractSms\InteractSms\Data\DeliveryReceipt;
use InteractSms\InteractSms\Data\InboundMessage;
use InteractSms\InteractSms\Data\ProcessingResult;
use InteractSms\InteractSms\Http\Client;
use InteractSms\InteractSms\Resources\Campaigns;
use InteractSms\InteractSms\Resources\SenderNames;
use InteractSms\InteractSms\Resources\Webhooks;

class InteractSms
{
    public function __construct(private readonly Client $client) {}

    public function campaigns(): Campaigns
    {
        return new Campaigns($this->client);
    }

    /**
     * @param  string|list<string|array<string, mixed>>  $to
     * @param  string|list<string>|null  $notifyId
     * @return array<string, mixed>
     */
    public function sendSms(
        string|array $to,
        string $text,
        ?string $from = null,
        ?string $campaignName = null,
        ?string $sendAt = null,
        string|array|null $notifyId = null,
    ): array {
        return $this->campaigns()->sendSms($to, $text, $from, $campaignName, $sendAt, $notifyId);
    }

    public function senderNames(): SenderNames
    {
        return new SenderNames($this->client);
    }

    /**
     * @return array<string, mixed>|list<mixed>
     */
    public function health(): array
    {
        return $this->client->health();
    }

    public function webhooks(): Webhooks
    {
        return new Webhooks($this->client->clientSecret);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function parseWebhook(array $payload): ProcessingResult|DeliveryReceipt|InboundMessage
    {
        return $this->webhooks()->parse($payload);
    }
}
