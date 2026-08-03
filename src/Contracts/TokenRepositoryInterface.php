<?php

namespace Fouladgar\OTP\Contracts;

interface TokenRepositoryInterface
{
    /**
     * Create a new token record. Pass $token to force a specific value (e.g. for tests) instead
     * of generating a random one.
     */
    public function create(string $recipient, string $purpose, ?string $token = null): string;

    /**
     * Determine if a token record exists and is valid.
     */
    public function exists(string $recipient, string $purpose): bool;

    /**
     * Determine if the given token matches the provided one.
     */
    public function isTokenMatching(string $recipient, string $purpose, string $token): bool;

    /**
     * Delete all existing tokens from the storage.
     */
    public function deleteExisting(string $recipient, string $purpose): bool;
}
