<?php

namespace Fouladgar\OTP\Exceptions;

use Exception;
use Fouladgar\OTP\Enums\OTPExceptionReason;

class OTPException extends Exception
{
    public function __construct(string $message, public readonly OTPExceptionReason $reason)
    {
        parent::__construct($message);
    }

    public static function whenOtpTokenIsInvalid(): static
    {
        return new static(__('OTP::otp.token_has_been_expired_or_invalid'), OTPExceptionReason::InvalidToken);
    }

    public static function whenOtpAlreadySent(): static
    {
        return new static(__('OTP::otp.otp_has_already_been_sent'), OTPExceptionReason::AlreadySent);
    }

    public static function whenChannelIsNotConfigured(): static
    {
        return new static(__('OTP::otp.channel_is_not_configured'), OTPExceptionReason::ChannelNotConfigured);
    }

    public static function whenTooManyAttempts(): static
    {
        return new static(__('OTP::otp.too_many_attempts'), OTPExceptionReason::TooManyAttempts);
    }
}
