<?php

namespace Fouladgar\OTP\Tests;

use Fouladgar\OTP\Enums\OTPExceptionReason;
use Fouladgar\OTP\Events\TokenValidationFailed;
use Fouladgar\OTP\Events\TooManyValidationAttempts;
use Fouladgar\OTP\Exceptions\OTPException;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;

class OTPRateLimitTest extends TestCase
{
    protected const RECIPIENT = '09000000000';

    #[Test]
    public function it_blocks_validation_after_too_many_failed_attempts(): void
    {
        config()->set('otp.rate_limit.max_attempts', 3);

        Notification::fake();

        $otp = OTP();
        $otp->send(self::RECIPIENT);

        for ($i = 0; $i < 3; $i++) {
            try {
                OTP()->validate(self::RECIPIENT, 'wrong');
                $this->fail('Expected an OTPException.');
            } catch (OTPException $e) {
                $this->assertSame(OTPExceptionReason::InvalidToken, $e->reason);
            }
        }

        Event::fake();

        try {
            OTP()->validate(self::RECIPIENT, $otp->getToken());
            $this->fail('Expected an OTPException.');
        } catch (OTPException $e) {
            $this->assertSame(OTPExceptionReason::TooManyAttempts, $e->reason);
        }

        Event::assertDispatched(TooManyValidationAttempts::class, fn ($event) => $event->recipient === self::RECIPIENT);
        Event::assertNotDispatched(TokenValidationFailed::class);
    }

    #[Test]
    public function it_does_not_rate_limit_when_disabled(): void
    {
        config()->set('otp.rate_limit.max_attempts', null);

        for ($i = 0; $i < 10; $i++) {
            try {
                OTP()->validate(self::RECIPIENT, 'wrong');
                $this->fail('Expected an OTPException.');
            } catch (OTPException $e) {
                $this->assertSame(OTPExceptionReason::InvalidToken, $e->reason);
            }
        }
    }

    #[Test]
    public function it_clears_attempts_after_a_successful_validation(): void
    {
        config()->set('otp.rate_limit.max_attempts', 2);

        Notification::fake();

        $otp = OTP();
        $otp->send(self::RECIPIENT);

        try {
            OTP()->validate(self::RECIPIENT, 'wrong');
        } catch (OTPException) {
        }

        $this->assertTrue(OTP()->validate(self::RECIPIENT, $otp->getToken()));

        // A fresh send/validate cycle should get its own fresh allowance, not inherit the old count.
        $otp = OTP();
        $otp->send(self::RECIPIENT);

        $this->assertTrue(OTP()->validate(self::RECIPIENT, $otp->getToken()));
    }

    #[Test]
    public function it_enforces_a_minimum_response_time_on_failed_validation(): void
    {
        config()->set('otp.validation_timebox_microseconds', 50_000);

        $start = microtime(true);

        try {
            OTP()->validate(self::RECIPIENT, 'wrong');
        } catch (OTPException) {
        }

        $this->assertGreaterThanOrEqual(0.04, microtime(true) - $start);
    }

    #[Test]
    public function it_does_not_delay_a_successful_validation(): void
    {
        config()->set('otp.validation_timebox_microseconds', 200_000);

        Notification::fake();

        $otp = OTP();
        $otp->send(self::RECIPIENT);

        $start = microtime(true);

        $this->assertTrue(OTP()->validate(self::RECIPIENT, $otp->getToken()));

        $this->assertLessThan(0.1, microtime(true) - $start);
    }
}
