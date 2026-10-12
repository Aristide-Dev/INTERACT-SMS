<?php

declare(strict_types=1);

namespace InteractSms\InteractSms\Resources;

use InteractSms\InteractSms\Http\Client;

final readonly class SenderNames
{
    public function __construct(private Client $client) {}

    /**
     * Sender ID types: 0 ComReg, 1 international, 2 numeric.
     *
     * @return list<array<string, mixed>>
     */
    public function list(): array
    {
        /** @var list<array<string, mixed>> $senders */
        $senders = $this->client->get('/senderlist');

        return $senders;
    }
}
