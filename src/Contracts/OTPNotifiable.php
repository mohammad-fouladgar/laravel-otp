<?php

namespace Fouladgar\OTP\Contracts;

interface OTPNotifiable
{
    /**
     * Send the OTP notification.
     */
    public function sendOTPNotification(string $token, array $channel): void;

    /**
     * Get the mobile number that should be used for sending via sms OTP notification.
     */
    public function getMobileForOTPNotification(): string;

    /**
     * Get the recipients of the given message.
     */
    public function routeNotificationForOTP(): string;
}
