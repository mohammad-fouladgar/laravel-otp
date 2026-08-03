<?php

declare(strict_types=1);

namespace Fouladgar\OTP\Events;

readonly class SendingNotification
{
    public function __construct(
        public string $recipient,
        public string $token,
        public array $channels
    ) {
    }
}
