<?php

declare(strict_types=1);

namespace Fouladgar\OTP\Events;

readonly class NotificationSent
{
    public function __construct(
        public string $recipient,
        public string $token,
        public array $channels
    ) {
    }
}
