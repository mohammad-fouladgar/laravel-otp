<?php

namespace Fouladgar\OTP\Contracts;

interface TokenRepositoryInterface
{
    /**
     * Create a new token record.
     */
    public function create(OTPNotifiable $user, string $indicator): string;

    /**
     * Determine if a token record exists and is valid.
     */
    public function exists(string $mobile, string $indicator): bool;

    /**
     * Determine if the given token matches the provided one.
     */
    public function isTokenMatching(OTPNotifiable $user, string $indicator, string $token): bool;

    /**
     * Delete all existing tokens from the storage.
     */
    public function deleteExisting(OTPNotifiable $user, string $indicator): bool;
}
