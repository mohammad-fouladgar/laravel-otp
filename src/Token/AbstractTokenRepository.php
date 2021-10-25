<?php

declare(strict_types=1);

namespace Fouladgar\OTP\Token;

use Fouladgar\OTP\Contracts\OTPNotifiable;
use Illuminate\Support\Carbon;

abstract class AbstractTokenRepository implements TokenRepositoryInterface
{
    /**
     * @var int
     */
    protected $expires;

    /**
     * @var int
     */
    protected $tokenLength;

    public function __construct(int $expires, int $tokenLength)
    {
        $this->expires = $expires;
        $this->tokenLength = $tokenLength;
    }

    public function create(OTPNotifiable $user): string
    {
        $mobile = $user->getMobileForOTPNotification();

        $this->deleteExisting($user);

        $token = $this->createNewToken();

        $this->save($mobile, $token);

        return $token;
    }

    protected function createNewToken(): string
    {
        return (string) random_int(10 ** ($this->tokenLength - 1), (10 ** $this->tokenLength) - 1);
    }

    protected function tokenExpired(string $expiresAt): bool
    {
        return Carbon::parse($expiresAt)->addMinutes($this->expires)->isPast();
    }

    protected function getPayload(string $mobile, string $token): array
    {
        return ['mobile' => $mobile, 'token' => $token];
    }

    /**
     * Insert into token storage.
     */
    abstract protected function save(string $mobile, string $token): bool;
}
