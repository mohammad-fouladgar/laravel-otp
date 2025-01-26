<?php

namespace Fouladgar\OTP\Exceptions;

use Exception;

class OTPException extends Exception
{
    public static function whenOtpTokenIsInvalid(): static
    {
        return new static(__('OTP::otp.token_has_been_expired_or_invalid'));
    }

    public static function whenUserNotFoundByMobile(): static
    {
        return new static(__('OTP::otp.user_not_found_by_mobile'));
    }

    public static function whenOtpAlreadySent(): static
    {
        return new static(__('OTP::otp.otp_has_already_been_sent_for_this_mobile'));
    }
}
