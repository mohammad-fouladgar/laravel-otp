<?php

namespace Fouladgar\OTP\Exceptions;

use Exception;

class UserNotFoundByMobileException extends Exception
{
    public function __construct()
    {
        parent::__construct('Not found user by mobile', 404);
    }
}
