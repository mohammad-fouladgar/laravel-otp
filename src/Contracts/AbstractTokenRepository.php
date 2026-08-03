<?php

declare(strict_types=1);

namespace Fouladgar\OTP\Contracts;

use Illuminate\Support\Carbon;

abstract class AbstractTokenRepository implements TokenRepositoryInterface
{
    public function __construct(protected int $expires, protected int $tokenLength)
    {
    }

    public function create(string $recipient, string $purpose, ?string $token = null): string
    {
        $this->deleteExisting($recipient, $purpose);

        $token ??= $this->createNewToken();

        $this->save($recipient, $purpose, $token);

        return $token;
    }

    protected function createNewToken(): string
    {
        return (string) random_int(10 ** ($this->tokenLength - 1), (10 ** $this->tokenLength) - 1);
    }

    protected function tokenExpired(string $expiresAt): bool
    {
        return Carbon::parse($expiresAt)->isPast();
    }

    protected function getPayload(string $recipient, string $purpose, string $token): array
    {
        return ['recipient' => $recipient, 'purpose' => $purpose, 'token' => $token, 'sent_at' => now()->toDateTimeString()];
    }

    /**
     * Insert into token storage.
     */
    abstract protected function save(string $recipient, string $purpose, string $token): bool;
}
