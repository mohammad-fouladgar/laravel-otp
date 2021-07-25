<?php

namespace Fouladgar\OTP\Notifications\Channels;

use Fouladgar\OTP\Notifications\Messages\OPTMessage;
use Illuminate\Notifications\Notification;

class OTPChannel
{
    public function send($notifiable, Notification $notification)
    {
        /** @var OPTMessage $message */
        $message = $notification->toSms($notifiable);

        dump(
            sprintf(
                'Through SMS Service this message: "%s" sent to this mobile number: "%s."',
                $message->getContent(),
                $message->getTo()
            )
        );
    }
}
