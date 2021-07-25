<?php

namespace Fouladgar\OTP\Tests;

use Fouladgar\OTP\Notifications\SendOTPNotification;
use Fouladgar\OTP\Tests\Models\OTPAbleUser;
use Fouladgar\OTP\Tests\TestCase;
use Fouladgar\OTP\Token\OTPBroker;

class SampleTest extends TestCase
{
    /** @test * */
    public function it_(): void
    {
        $otpBroker = new OTPBroker();
        $user      = new OTPAbleUser();
        $otpBroker->send($user);
    }
}
