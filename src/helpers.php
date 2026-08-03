<?php

use Fouladgar\OTP\Exceptions\OTPException;
use Fouladgar\OTP\OTPBroker;

if (! function_exists('OTP')) {
    /**
     * @throws OTPException|Throwable
     */
    function OTP(?string $recipient = null, mixed $options = null): OTPBroker|bool
    {
        /** @var OTPBroker $OTP */
        $OTP = app(OTPBroker::class);

        if (is_null($recipient)) {
            return $OTP;
        }

        if (is_null($options)) {
            return $OTP->send($recipient);
        }

        if (is_array($options)) {
            return $OTP->channel($options)->send($recipient);
        }

        return $OTP->validate($recipient, $options);
    }
}
