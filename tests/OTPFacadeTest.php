<?php

namespace Fouladgar\OTP\Tests;

use Fouladgar\OTP\Exceptions\OTPException;
use Fouladgar\OTP\Facades\OTP;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;

class OTPFacadeTest extends TestCase
{
    protected const RECIPIENT = '09389599530';

    #[Test]
    public function it_can_send_and_validate_a_token_via_the_facade(): void
    {
        Notification::fake();

        $this->assertTrue(OTP::send(self::RECIPIENT));

        $token = Cache::get(self::RECIPIENT)['token'];

        $this->assertTrue(OTP::validate(self::RECIPIENT, $token));
    }

    #[Test]
    public function it_throws_when_validating_an_invalid_token_via_the_facade(): void
    {
        Notification::fake();

        $this->expectException(OTPException::class);

        OTP::validate(self::RECIPIENT, 'invalid_token');
    }

    #[Test]
    public function it_can_disable_notifications_via_the_facade(): void
    {
        Notification::fake();
        config()->set('otp.channel', null);
        config()->set('otp.token_storage', 'cache');

        $this->assertTrue(OTP::withNotify(false)->send(self::RECIPIENT));

        Notification::assertNothingSent();
        $this->assertNotEmpty(Cache::get(config('otp.prefix') . self::RECIPIENT));
    }
}
