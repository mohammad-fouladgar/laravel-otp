<?php

namespace Fouladgar\OTP\Tests;

use Fouladgar\OTP\Notifications\Messages\OTPMessage;
use Illuminate\Notifications\Notification;

class CustomOTPChannel
{
    public function send($notifiable, Notification $notification): string
    {
        /** @var OTPMessage $message */
        $message = $notification->toSMS($notifiable);

        return sprintf(
            'send sms to: %s and token is %s',
            $message->getPayload()->to(),
            $message->getPayload()->content()
        );
    }
}
