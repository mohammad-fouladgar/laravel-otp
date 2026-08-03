<?php

declare(strict_types=1);

namespace Fouladgar\OTP\Events;

readonly class TokenRevoked
{
    public function __construct(
        public string $recipient,
        public string $purpose
    ) {
    }
}
