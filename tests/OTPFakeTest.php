<?php

namespace Fouladgar\OTP\Tests;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;

class OTPFakeTest extends TestCase
{
    protected const RECIPIENT = '5555555555';

    #[Test]
    public function it_can_fake_a_token_with_a_random_value(): void
    {
        Notification::fake();

        config()->set('otp.channel', null);

        $token = OTP()->fake(self::RECIPIENT);

        $this->assertNotEmpty($token);
        $this->assertTrue(OTP()->validate(self::RECIPIENT, $token));
        Notification::assertNothingSent();
    }

    #[Test]
    public function it_can_fake_a_token_with_a_specific_value(): void
    {
        Notification::fake();

        $token = OTP()->fake(self::RECIPIENT, '12345');

        $this->assertSame('12345', $token);
        $this->assertTrue(OTP()->validate(self::RECIPIENT, '12345'));
    }

    #[Test]
    public function it_can_fake_a_token_for_a_custom_purpose(): void
    {
        $token = OTP()->fake(self::RECIPIENT, '54321', 'login_');

        $this->assertSame('54321', Cache::get('login_' . self::RECIPIENT)['token']);
        $this->assertTrue(OTP()->purpose('login_')->validate(self::RECIPIENT, $token));
    }
}
