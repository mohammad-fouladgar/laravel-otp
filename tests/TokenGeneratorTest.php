<?php

namespace Fouladgar\OTP\Tests;

use Fouladgar\OTP\Contracts\TokenRepositoryInterface;
use Fouladgar\OTP\Token\Generators\AlphanumericAbstractTokenGenerator;
use Fouladgar\OTP\Token\Generators\NumericAbstractTokenGenerator;
use PHPUnit\Framework\Attributes\Test;

class TokenGeneratorTest extends TestCase
{
    #[Test]
    public function numeric_generator_produces_digits_of_the_requested_length(): void
    {
        $token = (new NumericAbstractTokenGenerator())->generate(6);

        $this->assertMatchesRegularExpression('/^\d{6}$/', $token);
    }

    #[Test]
    public function alphanumeric_generator_produces_characters_of_the_requested_length(): void
    {
        $token = (new AlphanumericAbstractTokenGenerator())->generate(8);

        $this->assertSame(8, strlen($token));
        $this->assertMatchesRegularExpression('/^[A-HJ-NP-Z2-9]{8}$/', $token);
    }

    #[Test]
    public function the_configured_generator_is_used_when_creating_a_token(): void
    {
        config()->set('otp.token_storage', 'cache');
        config()->set('otp.token_generator', AlphanumericAbstractTokenGenerator::class);

        $repository = $this->app->make(TokenRepositoryInterface::class);

        $token = $repository->create('5555555555', 'otp_');

        $this->assertMatchesRegularExpression('/^[A-HJ-NP-Z2-9]+$/', $token);
    }
}
