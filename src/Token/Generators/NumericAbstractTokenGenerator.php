<?php

declare(strict_types=1);

namespace Fouladgar\OTP\Token\Generators;

use Fouladgar\OTP\Contracts\AbstractTokenGenerator;

class NumericAbstractTokenGenerator extends AbstractTokenGenerator
{
    public function generate(int $length): string
    {
        return (string)random_int(10 ** ($length - 1), (10 ** $length) - 1);
    }
}
