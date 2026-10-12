<?php

declare(strict_types=1);

namespace InteractSms\InteractSms\Resources;

use InteractSms\InteractSms\Data\DeliveryReceipt;
use InteractSms\InteractSms\Data\InboundMessage;
use InteractSms\InteractSms\Data\ProcessingResult;
use InvalidArgumentException;

final readonly class Webhooks
{
    public function __construct(private string $clientSecret = '') {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function parse(array $payload): ProcessingResult|DeliveryReceipt|InboundMessage
    {
        if (array_key_exists('Status', $payload)) {
            return $this->parseProcessingResult($payload);
        }

        $type = $payload['SMS-Type'] ?? null;

        if (array_key_exists('SMS-Success', $payload) || $type === 'Notification') {
            return $this->parseDeliveryReceipt($payload);
        }

        if (array_key_exists('SMS-Content', $payload) || array_key_exists('SMS-From', $payload)) {
            return $this->parseInboundMessage($payload);
        }

        throw new InvalidArgumentException('Unrecognized Interact SMS webhook payload.');
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function parseProcessingResult(array $payload): ProcessingResult
    {
        return ProcessingResult::fromArray($payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function parseDeliveryReceipt(array $payload): DeliveryReceipt
    {
        return DeliveryReceipt::fromArray($payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function parseInboundMessage(array $payload): InboundMessage
    {
        return InboundMessage::fromArray($payload);
    }

    /**
     * @param  DeliveryReceipt|array<string, mixed>  $payload
     */
    public function verifyDeliveryReceipt(DeliveryReceipt|array $payload): bool
    {
        $receipt = $payload instanceof DeliveryReceipt ? $payload : DeliveryReceipt::fromArray($payload);

        return $receipt->matches($this->secret());
    }

    /**
     * @param  InboundMessage|array<string, mixed>  $payload
     */
    public function verifyInboundMessage(InboundMessage|array $payload): bool
    {
        $message = $payload instanceof InboundMessage ? $payload : InboundMessage::fromArray($payload);

        return $message->matches($this->secret());
    }

    private function secret(): string
    {
        if ($this->clientSecret === '') {
            throw new InvalidArgumentException('INTERACT_SMS_CLIENT_SECRET is required to verify webhook payloads.');
        }

        return $this->clientSecret;
    }
}
