<?php

declare(strict_types=1);

namespace Fouladgar\OTP\Token;

use Fouladgar\OTP\Contracts\AbstractTokenRepository;
use Illuminate\Contracts\Cache\Repository as Cache;
use Psr\SimpleCache\InvalidArgumentException;

class CacheTokenRepository extends AbstractTokenRepository
{
    public function __construct(
        protected Cache $cache,
        protected int $expires,
        protected int $tokenLength,
    ) {
        parent::__construct($expires, $tokenLength);
    }

    public function deleteExisting(string $recipient, string $purpose): bool
    {
        return $this->cache->forget($this->getSignatureKey($recipient, $purpose));
    }

    /**
     * @throws InvalidArgumentException
     */
    public function exists(string $recipient, string $purpose): bool
    {
        return $this->cache->has($this->getSignatureKey($recipient, $purpose));
    }

    /**
     * @throws InvalidArgumentException
     */
    public function isTokenMatching(string $recipient, string $purpose, string $token): bool
    {
        $exist = $this->exists($recipient, $purpose);
        $signature = $this->getSignatureKey($recipient, $purpose);

        return $exist && $this->cache->get($signature)['token'] === $token;
    }

    protected function save(string $recipient, string $purpose, string $token): bool
    {
        return $this->cache->put(
            $this->getSignatureKey($recipient, $purpose),
            $this->getPayload($recipient, $purpose, $token),
            now()->addMinutes($this->expires)
        );
    }

    protected function getSignatureKey($recipient, string $purpose): string
    {
        return sprintf('%s%s', $purpose, $recipient);
    }
}
