<?php

declare(strict_types=1);

namespace Fouladgar\OTP\Events;

/**
 * Dispatched when validate() is rate-limited because too many attempts were made for this
 * recipient/purpose within the configured time window. Useful for security monitoring
 * (e.g. detecting brute-force guessing).
 */
readonly class TooManyValidationAttempts
{
    public function __construct(
        public string $recipient,
        public string $purpose
    ) {
    }
}
