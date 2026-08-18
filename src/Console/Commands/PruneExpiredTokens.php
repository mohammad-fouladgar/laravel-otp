<?php

declare(strict_types=1);

namespace Fouladgar\OTP\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\ConnectionInterface;

class PruneExpiredTokens extends Command
{
    protected $signature = 'otp:prune';

    protected $description = "Delete expired OTP tokens from the database storage driver";

    public function handle(ConnectionInterface $connection): int
    {
        if (config('otp.token_storage') !== 'database') {
            $this->components->info('OTP token storage is not using the "database" driver — nothing to prune.');

            return self::SUCCESS;
        }

        $deleted = $connection->table(config('otp.token_table'))
            ->where('expires_at', '<', now())
            ->delete();

        $this->components->info("Deleted {$deleted} expired OTP token(s).");

        return self::SUCCESS;
    }
}
