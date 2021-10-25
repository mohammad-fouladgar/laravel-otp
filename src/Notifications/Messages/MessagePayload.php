<?php

namespace Fouladgar\OTP\Notifications\Messages;

class MessagePayload
{
    /**
     * @var string
     */
    private $to;

    /**
     * @var string
     */
    private $content;

    public function __construct(string $to, string $content)
    {
        $this->to = $to;
        $this->content = $content;
    }

    public function to(): string
    {
        return $this->to;
    }

    public function content(): string
    {
        return $this->content;
    }
}
