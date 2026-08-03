<?php

declare(strict_types=1);

namespace Fouladgar\OTP\Token;

use Fouladgar\OTP\Contracts\AbstractTokenRepository;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;

class DatabaseTokenRepository extends AbstractTokenRepository
{
    public function __construct(
        protected ConnectionInterface $connection,
        protected int                 $expires,
        protected int                 $tokenLength,
        protected string              $table
    ) {
        parent::__construct($expires, $tokenLength);
    }

    public function deleteExisting(string $recipient, string $purpose): bool
    {
        return (bool)optional($this->getTable()->where([
            'recipient' => $recipient,
            'purpose' => $purpose,
        ]))->delete();
    }

    protected function getLatestRecord(array $filters): ?array
    {
        $record = $this->getTable()
            ->where($filters)
            ->latest('id')
            ->first();

        return $record ? (array)$record : null;
    }

    public function exists(string $recipient, string $purpose): bool
    {
        $record = $this->getLatestRecord(['recipient' => $recipient, 'purpose' => $purpose]);

        return $record && ! $this->tokenExpired($record['expires_at']);
    }

    public function isTokenMatching(string $recipient, string $purpose, string $token): bool
    {
        $record = $this->getLatestRecord([
            'recipient' => $recipient,
            'token' => $token,
            'purpose' => $purpose,
        ]);

        return $record && ! $this->tokenExpired($record['expires_at']);
    }

    protected function getTable(): Builder
    {
        return $this->connection->table($this->table);
    }

    protected function save(string $recipient, string $purpose, string $token): bool
    {
        return $this->getTable()->insert($this->getPayload($recipient, $purpose, $token));
    }

    protected function getPayload(string $recipient, string $purpose, string $token): array
    {
        return parent::getPayload($recipient, $purpose, $token) +
            ['expires_at' => now()->addMinutes($this->expires), 'purpose' => $purpose];
    }
}
