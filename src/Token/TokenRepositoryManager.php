<?php

namespace Fouladgar\OTP\Token;

use Fouladgar\OTP\Contracts\TokenRepositoryInterface;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Manager;

class TokenRepositoryManager extends Manager
{
    public function getDefaultDriver(): string
    {
        return $this->config->get('otp.token_storage', 'cache');
    }

    protected function createCacheDriver(): TokenRepositoryInterface
    {
        return new CacheTokenRepository(
            $this->container->make(CacheRepository::class),
            $this->config->get('otp.token_lifetime', 5),
            $this->config->get('otp.token_length', 5),
            $this->config->get('otp.prefix'),
        );
    }

    protected function createDatabaseDriver(): TokenRepositoryInterface
    {
        return new DatabaseTokenRepository(
            $this->container->make(ConnectionInterface::class),
            $this->config->get('otp.token_lifetime'),
            $this->config->get('otp.token_length'),
            $this->config->get('otp.token_table'),
        );
    }
}
