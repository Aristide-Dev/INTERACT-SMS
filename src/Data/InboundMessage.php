<?php

declare(strict_types=1);

namespace InteractSms\InteractSms\Data;

final readonly class InboundMessage
{
    public const string OPT_OUT_SHORTCODE = '50123';

    public function __construct(
        public string $type,
        public string $content,
        public string $keyphrase,
        public string $network,
        public string $from,
        public string $to,
        public string $notifyId,
        public string $verify,
        public string $timestamp,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            type: self::stringValue($payload, 'SMS-Type'),
            content: self::stringValue($payload, 'SMS-Content'),
            keyphrase: self::stringValue($payload, 'SMS-Keyphrase'),
            network: self::stringValue($payload, 'SMS-Network'),
            from: self::stringValue($payload, 'SMS-From'),
            to: self::stringValue($payload, 'SMS-To'),
            notifyId: self::stringValue($payload, 'SMS-NotifyId'),
            verify: self::stringValue($payload, 'SMS-Verify'),
            timestamp: self::stringValue($payload, 'SMS-TimeStamp'),
        );
    }

    public function isOptOut(): bool
    {
        return $this->to === self::OPT_OUT_SHORTCODE;
    }

    public function matches(string $secret): bool
    {
        $expected = hash('md5', $this->from.$this->content.$secret);

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
}
