<?php

namespace Fouladgar\OTP\Exceptions;

use Exception;

class OTPException extends Exception
{
    public static function whenOtpTokenIsInvalid(): static
    {
        return new static('The token has been expired or invalid.');
    }

    public static function whenUserNotFoundByMobile(): static
    {
        return new static('User not found by mobile.');
    }

    public static function whenOtpAlreadySent(): static
    {
        return new static('OTP has already been sent for this mobile.');
    }
}
