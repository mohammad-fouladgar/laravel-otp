<?php

declare(strict_types=1);

namespace Fouladgar\OTP\Token;

use Fouladgar\OTP\Contracts\OTPNotifiable;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;

class DatabaseTokenRepository extends AbstractTokenRepository
{
    /**
     * @var ConnectionInterface
     */
    protected $connection;

    /**
     * @var string
     */
    protected $table;

    public function __construct(
        ConnectionInterface $connection,
        int $expires,
        int $tokenLength,
        string $table
    ) {
        parent::__construct($expires, $tokenLength);

        $this->table      = $table;
        $this->connection = $connection;
    }

    public function deleteExisting(OTPNotifiable $user): bool
    {
        return (bool) optional($this->getTable()->where('mobile', $user->getMobileForOTPNotification()))->delete();
    }

    public function exists(OTPNotifiable $user, string $token): bool
    {
        $record = (array) $this->getTable()
                               ->where('mobile', $user->getMobileForOTPNotification())
                               ->where('token', $token)
                               ->first();

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
