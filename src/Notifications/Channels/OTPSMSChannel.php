<?php

namespace Fouladgar\OTP\Notifications\Channels;

use Fouladgar\OTP\Contracts\SMSClient;
use Fouladgar\OTP\Notifications\Messages\OPTMessage;
use Illuminate\Notifications\Notification;

class OTPSMSChannel
{
    protected SMSClient $SMSClient;

    public function __construct(SMSClient $SMSClient)
    {
        $this->SMSClient = $SMSClient;
    }

    public function send($notifiable, Notification $notification)
    {
        if (!$notifiable->routeNotificationFor('otp', $notification)) {
            return;
        }

        /** @var OPTMessage $message */
        $message = $notification->toSMS($notifiable);

        return $this->SMSClient->sendMessage($message->getPayload());
    }
}
