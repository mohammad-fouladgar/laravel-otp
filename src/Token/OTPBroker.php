<?php

declare(strict_types=1);

namespace Fouladgar\OTP\Token;

use Fouladgar\OTP\Contracts\OTPNotifiable;

class OTPBroker
{
    private TokenRepositoryInterface $tokenRepository;

    public function __construct(TokenRepositoryInterface $tokenRepository)
    {
        $this->tokenRepository = $tokenRepository;
    }

    public function send(OTPNotifiable $user)
    {
        $user->sendOTPNotification('123456');
    }

    public function generate()
    {
    }

    public function validate($user, string $token)
    {
    }

    public function expiry(): self
    {
        return $this;
    }

    public function length(): self
    {
        return $this;
    }

    public function channel(): self
    {
        return $this;
    }

    public function revoke()
    {
    }

    /**
     * We may want to use it in the other classes.
     */
    public function tokenRepository(): TokenRepositoryInterface
    {
        return $this->tokenRepository;
    }
}
