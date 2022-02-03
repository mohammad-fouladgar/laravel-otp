<?php

namespace Fouladgar\OTP\Concerns;

use Fouladgar\OTP\Notifications\OTPNotification;

trait HasOTPNotify
{
    public function getFillable()
    {
        $this->appendMobileFieldToFillableAttributes();

        return $this->fillable;
    }

    public function sendOTPNotification(string $token, array $channel): void
    {
        $this->notify(new OTPNotification($token, $channel));
    }

    public function getMobileForOTPNotification(): string
    {
        return $this->{$this->getMobileField()};
    }

    public function routeNotificationForOTP(): string
    {
        return $this->{$this->getMobileField()};
    }

    private function appendMobileFieldToFillableAttributes(): void
    {
        $mobileFiled = $this->getMobileField();

        if (! in_array($mobileFiled, $this->fillable, true)) {
            $this->fillable = array_merge($this->fillable, [$mobileFiled]);
        }
    }

    private function getMobileField(): string
    {
        return config('otp.mobile_column', 'mobile');
    }
}
