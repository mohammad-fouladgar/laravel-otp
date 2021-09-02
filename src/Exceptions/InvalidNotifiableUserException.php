<?php

namespace Fouladgar\OTP\Exceptions;

use Exception;

class InvalidNotifiableUserException extends Exception
{
    public function __construct()
    {
        parent::__construct('The User model must be instance of "Fouladgar\OTP\Contracts\OTPNotifiable" interface.',
            406);
    }
}
