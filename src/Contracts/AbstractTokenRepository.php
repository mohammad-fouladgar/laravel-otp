<?php

declare(strict_types=1);

namespace Fouladgar\OTP\Contracts;

use Illuminate\Support\Carbon;

abstract class AbstractTokenRepository implements TokenRepositoryInterface
{
    public function __construct(protected int $expires, protected int $tokenLength)
    {
    }

    public function create(OTPNotifiable $user, string $indicator): string
    {
        $mobile = $user->getMobileForOTPNotification();

        $this->deleteExisting($user, $indicator);

        $token = $this->createNewToken();

        $this->save($mobile, $indicator, $token);

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

    protected function getPayload(string $mobile, string $indicator, string $token): array
    {
        return ['mobile' => $mobile, 'indicator' => $indicator, 'token' => $token, 'sent_at' => now()->toDateTimeString()];
    }

    /**
     * Insert into token storage.
     */
    abstract protected function save(string $mobile, string $indicator, string $token): bool;
}
