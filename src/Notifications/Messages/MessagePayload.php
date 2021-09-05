<?php

namespace Fouladgar\OTP\Notifications\Messages;

class MessagePayload
{
    private string $to;

    private string $content;

    public function __construct(string $to, string $content)
    {
        $this->to      = $to;
        $this->content = $content;
    }

    public function to()
    {
        return $this->to;
    }

    public function content()
    {
        return $this->content;
    }
}
