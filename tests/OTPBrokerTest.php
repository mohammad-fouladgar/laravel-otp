<?php

namespace Fouladgar\OTP\Tests;

use Fouladgar\OTP\Contracts\OTPNotifiable;
use Fouladgar\OTP\Exceptions\InvalidOTPTokenException;
use Fouladgar\OTP\Exceptions\UserNotFoundByMobileException;
use Fouladgar\OTP\Notifications\Channels\OTPSMSChannel;
use Fouladgar\OTP\Notifications\OTPNotification;
use Fouladgar\OTP\Tests\Models\OTPNotifiableUser;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;

class OTPBrokerTest extends TestCase
{
    protected const mobile = '09389599530';

    /**
     * @test
     */
    public function it_can_send_token_to_an_exist_user(): void
    {
        Notification::fake();

        $user = factory(OTPNotifiableUser::class)->create();
        $this->assertInstanceOf(OTPNotifiable::class, OTP()->send($user->mobile));

        Notification::assertSentTo(
            $user, OTPNotification::class
        );
    }

    /**
     * @test
     */
    public function it_can_send_token_to_user_that_does_not_exist(): void
    {
        Notification::fake();

        $user = OTP(self::mobile);
        $this->assertInstanceOf(OTPNotifiable::class, $user);

        Notification::assertSentTo(
            $user, OTPNotification::class
        );
    }

    /**
     * @test
     */
    public function it_can_send_token_with_using_default_channel(): void
    {
        Notification::fake();

        $user = OTP()->send(self::mobile);
        $this->assertInstanceOf(OTPNotifiable::class, $user);

        dump($this->app->version(), version_compare($this->app->version(), '6'));
        if (1 == version_compare($this->app->version(), '6')) {
            Notification::assertSentTo(
                $user,
                function (OTPNotification $notification, $channels) {
                    return $channels[0] == config('otp.channel');
                }
            );
        } else {
//            Notification::assertSentTo(
//                $user,
//                function ($notification, $channels) {
//                    return $channels[0] == config('otp.channel');
//                }
//            );
        }
    }

    /**
     * @test
     */
    public function it_can_send_token_with_using_specified_channels(): void
    {
        Notification::fake();

        $useChannels = [OTPSMSChannel::class, 'mail'];
        $user        = OTP(self::mobile, $useChannels);
        $this->assertInstanceOf(OTPNotifiable::class, $user);

        if (1 == version_compare($this->app->version(), '6')) {
            Notification::assertSentTo(
                $user,
                function (OTPNotification $notification, $channels) use ($useChannels) {
                    return $channels == $useChannels;
                }
            );
        } else {
//            Notification::assertSentTo(
//                $user,
//                function ($notification, $channels) use ($useChannels) {
//                    return $channels == $useChannels;
//                }
//            );
        }
    }

    /**
     * @test
     */
    public function it_can_send_token_with_using_extended_channel(): void
    {
        Notification::fake();

        $user = OTP()->channel('otp_sms')->send(self::mobile);
        $this->assertInstanceOf(OTPNotifiable::class, $user);

        if (1 == version_compare($this->app->version(), '6')) {
            Notification::assertSentTo(
                $user,
                function (OTPNotification $notification, $channels) {
                    return $channels == ['otp_sms'];
                }
            );
        } else {
//            Notification::assertSentTo(
//                $user,
//                function ($notification, $channels) {
//                    return $channels == ['otp_sms'];
//                }
//            );
        }
    }

    /**
     * @test
     */
    public function it_can_send_token_with_using_custom_channel(): void
    {
        Notification::fake();

        $user = OTP(self::mobile, [CustomOTPChannel::class]);
        $this->assertInstanceOf(OTPNotifiable::class, $user);

        if (1 == version_compare($this->app->version(), '6')) {
            Notification::assertSentTo(
                $user,
                function (OTPNotification $notification, $channels) {
                    return $channels == [CustomOTPChannel::class];
                }
            );
        } else {
//            Notification::assertSentTo(
//                $user,
//                function ($notification, $channels) {
//                    return $channels == [CustomOTPChannel::class];
//                }
//            );
        }
    }

    /**
     * @test
     */
    public function it_can_not_validate_a_token_when_user_not_found(): void
    {
        $this->expectException(UserNotFoundByMobileException::class);

        OTP()->validate(self::mobile, '12345');
    }

    /**
     * @test
     */
    public function it_can_not_validate_a_token_when_token_is_expired_or_invalid(): void
    {
        $user = factory(OTPNotifiableUser::class)->create();

        $this->expectException(InvalidOTPTokenException::class);

        OTP()->validate($user->mobile, '12345');
    }

    /**
     * @test
     */
    public function it_can_validate_a_valid_token(): void
    {
        $user = factory(OTPNotifiableUser::class)->create();

        OTP()->send($user->mobile);

        $user = OTP()->validate($user->mobile, Cache::get($user->mobile)['token']);

        $this->assertInstanceOf(OTPNotifiable::class, $user);

        // Database Storage
        config()->set('otp.token_storage', 'database');
        $otp = OTP();
        $otp->send($user->mobile);
        $this->assertInstanceOf(OTPNotifiable::class, OTP($user->mobile, $otp->getToken()));
    }

    /**
     * @test
     */
    public function it_can_revoke_a_token_successfully(): void
    {
        $user = factory(OTPNotifiableUser::class)->create();

        OTP($user->mobile);

        $this->assertTrue(OTP()->revoke($user));
        $this->assertFalse(OTP()->revoke($user));
    }
}
