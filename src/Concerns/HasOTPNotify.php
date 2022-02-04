<?php

namespace Fouladgar\OTP\Concerns;

use Fouladgar\OTP\Notifications\OTPNotification;

trait HasOTPNotify
{
    public function getFillable()
    {
        $this->appendMobileToFillableAttributes();

        return $this->fillable;
    }

    public function sendOTPNotification(string $token, array $channel): void
    {
        $this->notify(new OTPNotification($token, $channel));
    }

    public function getMobileForOTPNotification(): string
    {
        return $this->{$this->getOTPMobileField()};
    }

    public function routeNotificationForOTP(): string
    {
        return $this->{$this->getOTPMobileField()};
    }

    private function appendMobileToFillableAttributes(): void
    {
        $mobileFiled = $this->getOTPMobileField();

        if (! in_array($mobileFiled, $this->fillable, true)) {
            $this->fillable = array_merge($this->fillable, [$mobileFiled]);
        }
    }

    private function getOTPMobileField(): string
    {
        return config('otp.mobile_column', 'mobile');
    }
}
