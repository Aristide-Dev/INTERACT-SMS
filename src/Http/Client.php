<?php

declare(strict_types=1);

namespace InteractSms\InteractSms\Http;

use Closure;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use InteractSms\InteractSms\Exceptions\AuthenticationException;
use InteractSms\InteractSms\Exceptions\InteractSmsException;

final class Client
{
    private ?string $accessToken = null;

    private int $expiresAt = 0;

    public function __construct(
        public string $baseUrl,
        public string $authUrl,
        public string $healthUrl,
        public string $username,
        public string $password,
        public string $clientId,
        public int $timeout = 20,
        public string $senderName = '',
        public string $clientSecret = '',
    ) {}

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>|list<mixed>
     */
    public function get(string $path, array $query = []): array
    {
        return $this->send('GET', $path, query: $query);
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>|list<mixed>
     */
    public function post(string $path, array $body = []): array
    {
        return $this->send('POST', $path, body: $body);
    }

    /**
     * @return array<string, mixed>|list<mixed>
     */
    public function health(): array
    {
        $response = $this->attempt(fn (): Response => Http::acceptJson()
            ->timeout($this->timeout)
            ->get($this->healthUrl));

        if ($response->successful()) {
            $json = $response->json();

            return is_array($json) ? $json : ['status' => 'ok'];
        }

        throw InteractSmsException::fromResponse($response);
    }

    /**
     * @param  array<string, mixed>  $query
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>|list<mixed>
     */
    private function send(string $method, string $path, array $query = [], array $body = [], bool $retried = false): array
    {
        $response = $this->attempt(function () use ($method, $path, $query, $body): Response {
            $pending = $this->http();

            return match (strtoupper($method)) {
                'GET' => $pending->get($path, $query),
                'POST' => $pending->post($path, $body),
                default => throw new InteractSmsException("Unsupported HTTP method [{$method}]."),
            };
        });

        if ($response->status() === 401 && ! $retried) {
            $this->forgetAccessToken();

            return $this->send($method, $path, $query, $body, true);
        }

        return $this->decode($response);
    }

    private function http(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->withToken($this->accessToken())
            ->acceptJson()
            ->asJson()
            ->timeout($this->timeout)
            ->withHeaders([
                'X-InteractSms-Client' => 'aristide/interact-sms',
            ]);
    }

    private function accessToken(): string
    {
        if (is_string($this->accessToken) && time() < $this->expiresAt) {
            return $this->accessToken;
        }

        $response = $this->attempt(fn (): Response => Http::asForm()
            ->acceptJson()
            ->timeout($this->timeout)
            ->post($this->authUrl, [
                'grant_type' => 'password',
                'client_id' => $this->clientId,
                'username' => $this->username,
                'password' => $this->password,
            ]));

        if (! $response->successful()) {
            throw InteractSmsException::fromResponse($response);
        }

        $json = $response->json();

        if (! is_array($json)) {
            throw new AuthenticationException('Interact SMS did not return an access token.', $response->status());
        }

        $token = $json['access_token'] ?? null;

        if (! is_string($token) || $token === '') {
            throw new AuthenticationException('Interact SMS did not return an access token.', $response->status(), $json);
        }

        $expiresIn = (int) ($json['expires_in'] ?? 120);
        $this->accessToken = $token;
        $this->expiresAt = time() + max(1, $expiresIn - 5);

        return $token;
    }

    private function forgetAccessToken(): void
    {
        $this->accessToken = null;
        $this->expiresAt = 0;
    }

    /**
     * @param  Closure(): Response  $callback
     */
    private function attempt(Closure $callback): Response
    {
        try {
            return $callback();
        } catch (ConnectionException $exception) {
            throw new InteractSmsException($exception->getMessage(), 0, [], $exception);
        }
    }

    /**
     * @return array<string, mixed>|list<mixed>
     */
    private function decode(Response $response): array
    {
        if ($response->status() === 204) {
            return [];
        }

        if ($response->successful()) {
            $json = $response->json();

            return is_array($json) ? $json : [];
        }

        throw InteractSmsException::fromResponse($response);
    }
}
