<?php

declare(strict_types=1);

namespace Fouladgar\OTP\Contracts;

abstract class AbstractTokenGenerator
{
    abstract public function generate(int $length): string;
}
