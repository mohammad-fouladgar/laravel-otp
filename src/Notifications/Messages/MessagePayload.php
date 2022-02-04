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

    /**
     * @var string
     */
    private $from;

    public function __construct(string $to, string $content, string $from = '')
    {
        $this->to      = $to;
        $this->content = $content;
        $this->from    = $from;
    }

    public function to(): string
    {
        return $this->to;
    }

    public function content(): string
    {
        return $this->content;
    }

    public function from(): string
    {
        return $this->from;
    }
}
