<?php

declare(strict_types=1);

namespace Fouladgar\OTP\Token;

use Fouladgar\OTP\Contracts\AbstractTokenRepository;
use Fouladgar\OTP\Contracts\OTPNotifiable;
use Illuminate\Contracts\Cache\Repository as Cache;

class CacheTokenRepository extends AbstractTokenRepository
{
    public function __construct(
        protected Cache $cache,
        protected int $expires,
        protected int $tokenLength,
        protected string $prefix
    ) {
        parent::__construct($expires, $tokenLength);
    }

    public function deleteExisting(OTPNotifiable $user): bool
    {
        return $this->cache->forget($this->getSignatureKey($user->getMobileForOTPNotification()));
    }

    public function exists(string $mobile): bool
    {
        return $this->cache->has($this->getSignatureKey($mobile));
    }

    public function isTokenMatching(OTPNotifiable $user, string $token): bool
    {
        $exist = $this->exists($user->getMobileForOTPNotification());
        $signature = $this->getSignatureKey($user->getMobileForOTPNotification());

        return $exist && $this->cache->get($signature)['token'] === $token;
    }

    protected function save(string $mobile, string $token): bool
    {
        return $this->cache->add(
            $this->getSignatureKey($mobile),
            $this->getPayload($mobile, $token),
            now()->addMinutes($this->expires)
        );
    }

    protected function getSignatureKey($mobile): string
    {
        return $this->prefix.$mobile;
    }
}
