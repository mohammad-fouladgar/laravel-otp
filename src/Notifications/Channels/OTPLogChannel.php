<?php

declare(strict_types=1);

namespace Fouladgar\OTP\Notifications\Channels;

use Fouladgar\OTP\Notifications\OTPNotification;
use Psr\Log\LoggerInterface;

readonly class OTPLogChannel
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    public function send($notifiable, OTPNotification $notification): void
    {
        ['recipient' => $recipient, 'token' => $token] = $notification->toLog($notifiable);

        $this->logger->debug('OTP token generated', [
            'recipient' => $recipient,
            'token'     => $token,
        ]);
    }
}
