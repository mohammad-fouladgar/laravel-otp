<?php

namespace Fouladgar\OTP\Concerns;

use Fouladgar\OTP\Notifications\SendOTPNotification;

trait HasOTPNotify
{
    public function sendOTPNotification(string $token): void
    {
        $this->notify(new SendOTPNotification($token));
    }

    public function getMobileForOTPNotification(): string
    {
        return $this->mobile;
    }
}
