<?php

namespace Fouladgar\OTP\Tests;

use Fouladgar\OTP\Contracts\SMSClient;
use Fouladgar\OTP\Notifications\Messages\MessagePayload;

class SampleSMSClient implements SMSClient
{
    public function sendMessage(MessagePayload $payload): mixed
    {
        return null;
        // dump($payload->to(),$payload->content());
    }
}
