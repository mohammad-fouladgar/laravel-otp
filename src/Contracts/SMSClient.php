<?php

namespace Fouladgar\OTP\Contracts;

use Fouladgar\OTP\Notifications\Messages\MessagePayload;

interface SMSClient
{
    public function sendMessage(MessagePayload $payload): mixed;
}
