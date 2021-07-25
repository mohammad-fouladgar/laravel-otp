<?php

declare(strict_types=1);

namespace Fouladgar\OTP\Token;

use Fouladgar\OTP\Contracts\OTPNotifiable;
use Illuminate\Contracts\Cache\Repository as Cache;

class CacheTokenRepository extends AbstractTokenRepository
{
    private Cache $cache;

    public function __construct(int $expires, int $tokenLength, Cache $cache)
    {
        parent::__construct($expires, $tokenLength);

        $this->cache = $cache;
    }

    public function create(OTPNotifiable $user): string
    {
        $mobile = $user->getMobileForOTPNotification();

        $this->deleteExisting($user);

        $token = $this->createNewToken();

        $this->save($mobile, $token);

        return $token;
    }

    public function deleteExisting(OTPNotifiable $user): void
    {
        $this->cache->forget($user->getMobileForOTPNotification());
    }

    protected function save(string $mobile, string $token): bool
    {
        return $this->cache->add($mobile, $this->getPayload($mobile, $token));
    }

    public function exists(OTPNotifiable $user, string $token): bool
    {
        return $this->cache->has($user->getMobileForOTPNotification()) &&
            $this->cache->get($user->getMobileForOTPNotification())['token'] === $token;
    }
}
