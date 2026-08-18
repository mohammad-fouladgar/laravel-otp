<?php

use Fouladgar\OTP\OTPBroker;

if (! function_exists('OTP')) {
    function OTP(): OTPBroker
    {
        return app(OTPBroker::class);
    }
}
