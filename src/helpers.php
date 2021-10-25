<?php

use Fouladgar\OTP\Contracts\OTPNotifiable;
use Fouladgar\OTP\Exceptions\InvalidOTPTokenException;
use Fouladgar\OTP\Token\OTPBroker;

if (! function_exists('OTP')) {
    /**
     * @param  string|null  $mobile
     * @param  string|array|null  $token
     *
     * @return OTPBroker|OTPNotifiable
     * @throws InvalidOTPTokenException|Throwable
     */
    function OTP(?string $mobile = null, $token = null)
    {
        /** @var OTPBroker $OTP */
        $OTP = app(OTPBroker::class);

        if (is_null($mobile)) {
            return $OTP;
        }

        if (is_null($token)) {
            return $OTP->send($mobile);
        }

        if (is_array($token)) {
            return $OTP->channel($token)->send($mobile);
        }

        return $OTP->validate($mobile, $token);
    }
}
