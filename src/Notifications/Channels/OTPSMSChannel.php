<?php

namespace Fouladgar\OTP\Notifications\Channels;

use Fouladgar\OTP\Contracts\OTPNotifiable;
use Fouladgar\OTP\Contracts\SMSClient;
use Fouladgar\OTP\Notifications\Messages\OTPMessage;
use Illuminate\Notifications\Notification;

class OTPSMSChannel
{
    /**
     * @var SMSClient
     */
    protected $SMSClient;

    public function __construct(SMSClient $SMSClient)
    {
        $this->SMSClient = $SMSClient;
    }

    public function send(OTPNotifiable $notifiable, Notification $notification)
    {
        if (!$notifiable->routeNotificationFor('otp', $notification)) {
            return;
        }

        /** @var OTPMessage $message */
        $message = $notification->toSMS($notifiable);

        return $this->SMSClient->sendMessage($message->getPayload());
    }
}
