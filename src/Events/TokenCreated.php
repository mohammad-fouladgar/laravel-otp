<?php

declare(strict_types=1);

namespace Fouladgar\OTP\Events;

readonly class TokenCreated
{
    public function __construct(
        public string $recipient,
        public string $token,
        public string $purpose
    ) {
    }
}
