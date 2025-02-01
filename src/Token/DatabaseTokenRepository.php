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
        protected int                 $expires,
        protected int                 $tokenLength,
        protected string              $table
    )
    {
        parent::__construct($expires, $tokenLength);
    }

    public function deleteExisting(OTPNotifiable $user, string $indicator): bool
    {
        return (bool)optional($this->getTable()->where([
            'mobile' => $user->getMobileForOTPNotification(),
            'indicator' => $indicator
        ]))->delete();
    }

    protected function getLatestRecord(array $filters): ?array
    {
        $record = $this->getTable()
            ->where($filters)
            ->latest()
            ->first();

        return $record ? (array)$record : null;
    }

    public function exists(string $mobile, string $indicator): bool
    {
        $record = $this->getLatestRecord(['mobile' => $mobile, 'indicator' => $indicator]);

        return $record && !$this->tokenExpired($record['expires_at']);
    }

    public function isTokenMatching(OTPNotifiable $user, string $indicator, string $token): bool
    {
        $record = $this->getLatestRecord([
            'mobile' => $user->getMobileForOTPNotification(),
            'token' => $token,
            'indicator' => $indicator,
        ]);

        return $record && !$this->tokenExpired($record['expires_at']);
    }

    protected function getTable(): Builder
    {
        return $this->connection->table($this->table);
    }

    protected function save(string $mobile, string $indicator, string $token): bool
    {
        return $this->getTable()->insert($this->getPayload($mobile, $indicator, $token));
    }

    protected function getPayload(string $mobile, string $indicator, string $token): array
    {
        return parent::getPayload($mobile, $indicator, $token) +
            ['expires_at' => now()->addMinutes($this->expires), 'indicator' => $indicator];
    }
}
