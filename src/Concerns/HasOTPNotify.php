<?php

namespace Fouladgar\OTP\Concerns;

use Fouladgar\OTP\Notifications\SendOTPNotification;

trait HasOTPNotify
{
    public function getFillable()
    {
        $this->appendMobileFieldToFillableAttributes();

        return $this->fillable;
    }

    public function sendOTPNotification(string $token, array $channel): void
    {
        $this->notify(new SendOTPNotification($token, $channel));
    }

    public function getMobileForOTPNotification(): string
    {
        return $this->mobile;
    }

    private function appendMobileFieldToFillableAttributes(): void
    {
        $mobileFiled = $this->getMobileField();

        if (!in_array($mobileFiled, $this->fillable, true)) {
            $this->fillable = array_merge($this->fillable, [$mobileFiled]);
        }
    }

    private function getMobileField(): string
    {
        return config('otp.mobile_column', 'mobile');
    }
}
