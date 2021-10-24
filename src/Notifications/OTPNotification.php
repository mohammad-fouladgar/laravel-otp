<?php

declare(strict_types=1);

namespace Fouladgar\OTP\Notifications;

use Closure;
use Fouladgar\OTP\Contracts\OTPNotifiable;
use Fouladgar\OTP\Notifications\Messages\OTPMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Lang;

class OTPNotification extends Notification
{
    /**
     * @var Closure|null
     */
    public static $toMailCallback = null;

    /**
     * @var Closure|null
     */
    public static $toSMSCallback = null;

    /**
     * @var string
     */
    private $token;

    /**
     * @var array
     */
    private $channels;

    public function __construct(string $token, array $channels)
    {
        $this->token    = $token;
        $this->channels = $channels;
    }

    public static function toMailUsing(Closure $callback): void
    {
        static::$toMailCallback = $callback;
    }

    public static function toSMSUsing(Closure $callback): void
    {
        static::$toSMSCallback = $callback;
    }

    public function via($notifiable): array
    {
        return $this->channels;
    }

    public function toSMS($notifiable)
    {
        if (static::$toSMSCallback) {
            return call_user_func(static::$toSMSCallback, $notifiable, $this->token);
        }

        return $this->buildSMSMessage($notifiable);
    }

    public function toMail($notifiable)
    {
        if (static::$toMailCallback) {
            return call_user_func(static::$toMailCallback, $notifiable, $this->token);
        }

        return $this->buildMailMessage();
    }

    protected function buildSMSMessage($notifiable): OTPMessage
    {
        return (new OTPMessage())
            ->to($notifiable->getMobileForOTPNotification())
            ->content(Lang::get('OTP::otp.otp_token', ['token' => $this->token]));
    }

    protected function buildMailMessage(): MailMessage
    {
        return (new MailMessage)
            ->subject(Lang::get('OTP Login'))
            ->greeting(Lang::get('OTP Login'))
            ->line(Lang::get('Your OTP code is: :token.', ['token' => $this->token]));
    }
}
