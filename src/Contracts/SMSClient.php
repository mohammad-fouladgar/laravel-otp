<?php

namespace Fouladgar\OTP\Contracts;

use Fouladgar\OTP\Notifications\Messages\MessagePayload;

interface SMSClient
{
    /**
     * @return mixed
     */
    public function sendMessage(MessagePayload $payload);
}
