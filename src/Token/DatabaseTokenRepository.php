<?php

declare(strict_types=1);

namespace Fouladgar\OTP\Token;

use Fouladgar\OTP\Contracts\AbstractTokenRepository;
use Fouladgar\OTP\Contracts\OTPNotifiable;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;

class DatabaseTokenRepository extends AbstractTokenRepository
{
    public function __construct(
        protected ConnectionInterface $connection,
        protected int $expires,
        protected int $tokenLength,
        protected string $table
    ) {
        parent::__construct($expires, $tokenLength);
    }

    public function deleteExisting(OTPNotifiable $user): bool
    {
        return (bool) optional($this->getTable()->where('mobile', $user->getMobileForOTPNotification()))->delete();
    }

    protected function getLatestRecord(array $filters): ?array
    {
        $record = $this->getTable()
            ->where($filters)
            ->latest()
            ->first();

        return $record ? (array) $record : null;
    }

    public function exists(string $mobile): bool
    {
        $record = $this->getLatestRecord(['mobile' => $mobile]);

        return $record && !$this->tokenExpired($record['expires_at']);
    }

    public function isTokenMatching(OTPNotifiable $user, string $token): bool
    {
        $record = $this->getLatestRecord([
            'mobile' => $user->getMobileForOTPNotification(),
            'token'  => $token,
        ]);

        return $record && !$this->tokenExpired($record['expires_at']);
    }

    protected function getTable(): Builder
    {
        return $this->connection->table($this->table);
    }

    protected function save(string $mobile, string $token): bool
    {
        return $this->getTable()->insert($this->getPayload($mobile, $token));
    }

    protected function getPayload(string $mobile, string $token): array
    {
        return parent::getPayload($mobile, $token) + ['expires_at' => now()->addMinutes($this->expires)];
    }
}
