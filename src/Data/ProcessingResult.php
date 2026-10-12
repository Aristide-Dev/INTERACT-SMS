<?php

declare(strict_types=1);

namespace InteractSms\InteractSms\Data;

final readonly class ProcessingResult
{
    public const string MESSAGE_SUCCESS = 'MESSAGE_SUCCESS';

    public const string MESSAGE_ERROR = 'MESSAGE_ERROR';

    public const string CAMPAIGN_ERROR = 'CAMPAIGN_ERROR';

    public function __construct(
        public string $status,
        public string $finishedAt,
        public string $message,
        public ?string $notifyId = null,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        $notifyId = $payload['NotifyId'] ?? null;

        return new self(
            status: is_scalar($payload['Status'] ?? null) ? (string) $payload['Status'] : '',
            finishedAt: is_scalar($payload['FinishedAt'] ?? null) ? (string) $payload['FinishedAt'] : '',
            message: is_scalar($payload['Message'] ?? null) ? (string) $payload['Message'] : '',
            notifyId: is_scalar($notifyId) ? (string) $notifyId : null,
        );
    }

    public function accepted(): bool
    {
        return $this->status === self::MESSAGE_SUCCESS;
    }
}
