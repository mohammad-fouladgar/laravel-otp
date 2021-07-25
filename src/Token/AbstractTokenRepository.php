<?php

declare(strict_types=1);

namespace Fouladgar\OTP\Token;

use Illuminate\Support\Carbon;

abstract class AbstractTokenRepository implements TokenRepositoryInterface
{
    /**
     * The number of seconds a token should last.
     */
    protected int $expires;

    protected int $tokenLength;

    public function __construct(int $expires, int $tokenLength)
    {
        $this->expires = $expires;
        $this->tokenLength = $tokenLength;
    }

    /**
     * Set Expires token.
     */
    public function setExpires(int $expires): self
    {
        $this->expires = $expires;

        return $this;
    }

    /**
     * Create a new token for user.
     */
    protected function createNewToken(): string
    {
        return (string) random_int(10 ** ($this->tokenLength - 1), (10 ** $this->tokenLength) - 1);
    }

    /**
     * Determine if the token has expired.
     */
    protected function tokenExpired(string $expiresAt): bool
    {
        return Carbon::parse($expiresAt)->addMinutes($this->expires)->isPast();
    }

    /**
     * Build the record payload for the table.
     */
    protected function getPayload(string $mobile, string $token): array
    {
        return ['mobile' => $mobile, 'token' => $token];
    }

    /**
     * Insert into token storage.
     */
    abstract protected function save(string $mobile, string $token): bool;
}
