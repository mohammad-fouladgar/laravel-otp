<?php

declare(strict_types=1);

namespace Fouladgar\OTP\Token;

use Fouladgar\OTP\Contracts\AbstractTokenRepository;
use Fouladgar\OTP\Contracts\OTPNotifiable;
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

    public function deleteExisting(OTPNotifiable $user, string $indicator): bool
    {
        return $this->cache->forget($this->getSignatureKey($user->getMobileForOTPNotification(), $indicator));
    }

    /**
     * @throws InvalidArgumentException
     */
    public function exists(string $mobile, string $indicator): bool
    {
        return $this->cache->has($this->getSignatureKey($mobile, $indicator));
    }

    /**
     * @throws InvalidArgumentException
     */
    public function isTokenMatching(OTPNotifiable $user, string $indicator, string $token): bool
    {
        $exist = $this->exists($user->getMobileForOTPNotification(), $indicator);
        $signature = $this->getSignatureKey($user->getMobileForOTPNotification(), $indicator);

        return $exist && $this->cache->get($signature)['token'] === $token;
    }

    protected function save(string $mobile, string $indicator, string $token): bool
    {
        return $this->cache->add(
            $this->getSignatureKey($mobile, $indicator),
            $this->getPayload($mobile, $indicator, $token),
            now()->addMinutes($this->expires)
        );
    }

    protected function getSignatureKey($mobile, string $indicator): string
    {
        return sprintf('%s%s', $indicator, $mobile);
    }
}
