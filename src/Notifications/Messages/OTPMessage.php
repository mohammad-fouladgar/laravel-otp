<?php

namespace Fouladgar\OTP\Notifications\Messages;

class OTPMessage
{
    private string $content;

    private string $to;

    private string $from = '';

    private mixed $template = null;

    public function content(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function to(string $to): static
    {
        $this->to = $to;

        return $this;
    }

    public function from(string $from): static
    {
        $this->from = $from;

        return $this;
    }

    public function template(mixed $template): static
    {
        $this->template = $template;

        return $this;
    }

    public function getPayload(): MessagePayload
    {
        return (new MessagePayload($this->to, $this->content, $this->from, $this->template));
    }
}
