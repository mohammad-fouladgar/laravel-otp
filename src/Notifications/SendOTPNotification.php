<?php

namespace Fouladgar\OTP\Notifications;

use Fouladgar\OTP\Notifications\Messages\OPTMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SendOTPNotification extends Notification
{
    private string $token;

    private array $channels;

    public function __construct(string $token, array $channels)
    {
        $this->token   = $token;
        $this->channels = $channels;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     *
     * @return array
     */
    public function via($notifiable)
    {
        return $this->channels;
    }

    public function toSMS($notifiable)
    {
        return (new OPTMessage())
            ->to($notifiable->getMobileForOTPNotification())
            ->content('Your OTP code is: '.$this->token); // todo: get from lang file
    }

    public function toMail()
    {
        // todo: use an callback
        dump('email');
        return (new MailMessage)
            ->greeting('Otp')
            ->line('Your OTP code is: '.$this->token);
    }
}
