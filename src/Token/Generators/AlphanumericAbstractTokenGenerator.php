<?php

declare(strict_types=1);

namespace Fouladgar\OTP\Token\Generators;

use Fouladgar\OTP\Contracts\AbstractTokenGenerator;

class AlphanumericAbstractTokenGenerator extends AbstractTokenGenerator
{
    private const ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    public function generate(int $length): string
    {
        $max = strlen(self::ALPHABET) - 1;
        $token = '';

        for ($i = 0; $i < $length; $i++) {
            $token .= self::ALPHABET[random_int(0, $max)];
        }

        return $token;
    }
}
