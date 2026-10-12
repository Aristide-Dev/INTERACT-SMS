<?php

declare(strict_types=1);

namespace InteractSms\InteractSms\Data;

final readonly class DeliveryReceipt
{
    public function __construct(
        public string $type,
        public string $notifyId,
        public string $success,
        public string $to,
        public string $from,
        public string $verify,
        public int $totalToSend,
        public int $totalSent,
        public int $totalFailed,
        public string $timestamp,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            type: self::stringValue($payload, 'SMS-Type'),
            notifyId: self::stringValue($payload, 'SMS-NotifyId'),
            success: self::stringValue($payload, 'SMS-Success'),
            to: self::stringValue($payload, 'SMS-To'),
            from: self::stringValue($payload, 'SMS-From'),
            verify: self::stringValue($payload, 'SMS-Verify'),
            totalToSend: self::intValue($payload, 'SMS-TotalToSend'),
            totalSent: self::intValue($payload, 'SMS-TotalSent'),
            totalFailed: self::intValue($payload, 'SMS-TotalFailed'),
            timestamp: self::stringValue($payload, 'SMS-TimeStamp'),
        );
    }

    public function delivered(): bool
    {
        return strcasecmp($this->success, 'true') === 0;
    }

    public function matches(string $secret): bool
    {
        $expected = hash('md5', $this->notifyId.$this->success.$secret);

        return hash_equals(strtolower($expected), strtolower($this->verify));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private static function stringValue(array $payload, string $key): string
    {
        $value = $payload[$key] ?? '';

        return is_scalar($value) ? (string) $value : '';
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private static function intValue(array $payload, string $key): int
    {
        $value = $payload[$key] ?? 0;

        return is_numeric($value) ? (int) $value : 0;
    }
}
