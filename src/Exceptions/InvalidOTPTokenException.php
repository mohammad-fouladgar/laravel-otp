<?php

namespace Fouladgar\OTP\Exceptions;

use Exception;

class InvalidOTPTokenException extends Exception
{
    public function __construct()
    {
        parent::__construct('The token has been expired or invalid.', 406);
    }
}
