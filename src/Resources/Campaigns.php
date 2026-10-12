<?php

declare(strict_types=1);

namespace InteractSms\InteractSms\Resources;

use InteractSms\InteractSms\Http\Client;
use InvalidArgumentException;

final readonly class Campaigns
{
    public const int MAX_RECIPIENTS = 400_000;

    public const int MAX_TEXT_LENGTH = 2000;

    public const int MAX_SENDER_LENGTH = 20;

    public const int MAX_CAMPAIGN_NAME_LENGTH = 30;

    public const int MAX_NOTIFY_ID_LENGTH = 30;

    public function __construct(private Client $client) {}

    /**
     * Send a campaign. `sendAt` is an Irish local time (`Europe/Dublin`), format `yyyy-MM-ddTHH:mm`.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function send(array $payload): array
    {
        $payload['recipientInfo'] = $this->recipients($payload['recipientInfo'] ?? []);

        return $this->submit($payload);
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
        if ($campaignName !== null && strlen($campaignName) > self::MAX_CAMPAIGN_NAME_LENGTH) {
            throw new InvalidArgumentException('Campaign name must be at most '.self::MAX_CAMPAIGN_NAME_LENGTH.' characters.');
        }

        $payload = [
            'text' => $text,
            'from' => $from ?? $this->client->senderName,
            'recipientInfo' => $this->recipientInfo($to, $notifyId),
        ];

        if ($campaignName !== null) {
            $payload['campaignName'] = $campaignName;
        }

        if ($sendAt !== null) {
            $payload['sendAt'] = $sendAt;
        }

        return $this->submit($payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function submit(array $payload): array
    {
        $from = $payload['from'] ?? $this->client->senderName;
        $text = $payload['text'] ?? '';
        $recipients = $payload['recipientInfo'] ?? [];

        if (! is_string($text) || trim($text) === '') {
            throw new InvalidArgumentException('Message text cannot be empty.');
        }

        if (strlen($text) > self::MAX_TEXT_LENGTH) {
            throw new InvalidArgumentException('Message text must be at most '.self::MAX_TEXT_LENGTH.' characters.');
        }

        if (! is_string($from) || trim($from) === '') {
            throw new InvalidArgumentException('A sender name is required.');
        }

        if (strlen($from) > self::MAX_SENDER_LENGTH) {
            throw new InvalidArgumentException('Sender name must be at most '.self::MAX_SENDER_LENGTH.' characters.');
        }

        if (! is_array($recipients) || $recipients === []) {
            throw new InvalidArgumentException('At least one recipient is required.');
        }

        if (count($recipients) > self::MAX_RECIPIENTS) {
            throw new InvalidArgumentException('A campaign accepts at most '.self::MAX_RECIPIENTS.' recipients.');
        }

        $payload['text'] = $text;
        $payload['from'] = $from;
        $payload['recipientInfo'] = $recipients;

        /** @var array<string, mixed> $response */
        $response = $this->client->post('/Campaign', $payload);

        return $response;
    }

    /**
     * @param  string|array<array-key, mixed>  $to
     * @param  string|array<int, string>|null  $notifyId
     * @return list<array{msisdn: string, notifyId?: string}>
     */
    private function recipientInfo(string|array $to, string|array|null $notifyId): array
    {
        $items = is_array($to) ? array_values($to) : [$to];

        if (count($items) > self::MAX_RECIPIENTS) {
            throw new InvalidArgumentException('A campaign accepts at most '.self::MAX_RECIPIENTS.' recipients.');
        }

        if (is_string($notifyId) && $notifyId !== '' && count($items) > 1) {
            throw new InvalidArgumentException('Provide a notifyId per recipient when sending to more than one number.');
        }

        $recipients = [];

        foreach ($items as $index => $item) {
            $msisdn = '';
            $id = null;

            if (is_array($item)) {
                $number = $item['msisdn'] ?? '';
                $msisdn = is_scalar($number) ? trim((string) $number) : '';
                $id = $item['notifyId'] ?? null;
            } elseif (is_scalar($item)) {
                $msisdn = trim((string) $item);
            }

            if ($msisdn === '') {
                continue;
            }

            $row = ['msisdn' => $msisdn];
            $resolved = $this->notifyId($id, $notifyId, $index);

            if ($resolved !== null) {
                $row['notifyId'] = $resolved;
            }

            $recipients[] = $row;
        }

        return $recipients;
    }

    /**
     * @return list<array{msisdn: string, notifyId?: string}>
     */
    private function recipients(mixed $to): array
    {
        if (is_array($to)) {
            return $this->recipientInfo($to, null);
        }

        if (is_scalar($to)) {
            return $this->recipientInfo((string) $to, null);
        }

        return [];
    }

    /**
     * @param  string|array<int, string>|null  $notifyId
     */
    private function notifyId(mixed $existing, string|array|null $notifyId, int $index): ?string
    {
        $value = null;

        if (is_scalar($existing) && (string) $existing !== '') {
            $value = (string) $existing;
        } elseif (is_string($notifyId) && $notifyId !== '') {
            $value = $notifyId;
        } elseif (is_array($notifyId) && array_key_exists($index, $notifyId) && $notifyId[$index] !== '') {
            $value = $notifyId[$index];
        }

        if ($value === null) {
            return null;
        }

        if (strlen($value) > self::MAX_NOTIFY_ID_LENGTH) {
            throw new InvalidArgumentException('notifyId must be at most '.self::MAX_NOTIFY_ID_LENGTH.' characters.');
        }

        return $value;
    }
}
