<?php

declare(strict_types=1);

namespace InteractSms\InteractSms\Exceptions;

use Illuminate\Http\Client\Response;
use RuntimeException;
use Throwable;

class InteractSmsException extends RuntimeException
{
    /**
     * @param  array<string, mixed>|list<mixed>  $body
     */
    public function __construct(
        string $message,
        public readonly int $status = 0,
        public readonly array $body = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $status, $previous);
    }

    public static function fromResponse(Response $response): self
    {
        $body = $response->json();
        $payload = is_array($body) ? $body : ['body' => $response->body()];
        $message = self::extractMessage($payload) ?? ($response->reason() ?: 'Interact SMS API error');
        $status = $response->status();

        return match (true) {
            $status === 401 => new AuthenticationException($message, $status, $payload),
            $status === 404 => new NotFoundException($message, $status, $payload),
            $status === 400, $status === 415, $status === 422 => new ValidationException($message, $status, $payload),
            $status === 429 => new RateLimitException($message, $status, $payload),
            $status >= 500 => new ServerException($message, $status, $payload),
            default => new self($message, $status, $payload),
        };
    }

    /**
     * @param  array<string, mixed>|list<mixed>  $payload
     */
    public static function extractMessage(array $payload): ?string
    {
        $errors = $payload['errors'] ?? null;

        if (is_array($errors)) {
            $parts = [];

            foreach ($errors as $error) {
                if (is_string($error) && $error !== '') {
                    $parts[] = $error;
                }
            }

            if ($parts !== []) {
                return implode(' ', $parts);
            }
        }

        foreach (['error_description', 'message', 'detail', 'error'] as $key) {
            $value = $payload[$key] ?? null;

            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return null;
    }
}
