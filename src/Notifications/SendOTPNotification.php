<?php

namespace Fouladgar\OTP\Notifications;

use Fouladgar\OTP\Notifications\Channels\OTPChannel;
use Fouladgar\OTP\Notifications\Messages\OPTMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SendOTPNotification extends Notification
{
    private string $token;


    public function __construct(string $token)
    {
        $this->token = $token;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     *
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail', 'sms'];
    }

    public function toSms($notifiable)
    {
        return (new OPTMessage())
            ->to($notifiable->getMobileForOTPNotification())
            ->content('Your OTP code is: ' . $this->token); // todo: get from lang file
    }

    public function toMail()
    {
        return (new MailMessage)
            ->greeting('Otp')
            ->line('Your OTP code is: ' . $this->token);
    }
}
