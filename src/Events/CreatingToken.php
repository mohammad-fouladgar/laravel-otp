<?php

declare(strict_types=1);

namespace Fouladgar\OTP\Events;

readonly class CreatingToken
{
    public function __construct(
        public string $recipient,
        public string $purpose
    ) {
    }
}
