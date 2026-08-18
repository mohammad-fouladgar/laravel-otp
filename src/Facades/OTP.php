<?php

declare(strict_types=1);

namespace Fouladgar\OTP\Facades;

use Fouladgar\OTP\OTPBroker;
use Illuminate\Support\Facades\Facade;

/**
 * @method static OTPBroker channel(array|string $channel)
 * @method static OTPBroker purpose(string $purpose)
 * @method static OTPBroker withNotify(bool $withNotify = true)
 * @method static bool send(string $recipient)
 * @method static bool validate(string $recipient, string $token)
 * @method static bool revoke(string $recipient)
 * @method static string|null getToken()
 * @method static string fake(string $recipient, string|null $token = null, string|null $purpose = null)
 *
 * @see OTPBroker
 */
class OTP extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return OTPBroker::class;
    }
}
