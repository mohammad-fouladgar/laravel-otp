<?php

namespace Fouladgar\OTP\Notifications\Messages;

class MessagePayload
{
    public function __construct(
        private string $to,
        private string $content,
        private string $from = '',
        private mixed $template = null
    )
    {
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

    public function template(): mixed
    {
        return $this->template;
    }
}
