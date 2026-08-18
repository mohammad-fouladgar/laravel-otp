<?php

namespace Fouladgar\OTP\Tests;

use Fouladgar\OTP\Exceptions\OTPException;
use Fouladgar\OTP\Notifications\OTPNotification;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;

class OTPBrokerTest extends TestCase
{
    protected const RECIPIENT = '5555555555';

    #[Test]
    public function it_can_not_send_when_no_channel_is_configured(): void
    {
        config()->set('otp.channel', null);

        $this->expectException(OTPException::class);

        OTP()->send(self::RECIPIENT);
    }

    #[Test]
    public function it_can_send_with_notifications_disabled(): void
    {
        Notification::fake();
        config()->set('otp.channel', null);
        config()->set('otp.token_storage', 'cache');

        $otp = OTP()->withNotify(false);
        $this->assertTrue($otp->send(self::RECIPIENT));

        Notification::assertNothingSent();
        $this->assertNotNull($otp->getToken());
    }

    #[Test]
    public function it_can_send_with_notifications_disabled_via_config(): void
    {
        Notification::fake();
        config()->set('otp.channel', null);
        config()->set('otp.with_notify', false);

        $otp = OTP();
        $this->assertTrue($otp->send(self::RECIPIENT));

        Notification::assertNothingSent();
        $this->assertNotNull($otp->getToken());
    }

    #[Test]
    public function it_can_validate_a_token_created_with_notifications_disabled(): void
    {
        $otp = OTP()->withNotify(false);
        $otp->send(self::RECIPIENT);

        $this->assertTrue(OTP()->validate(self::RECIPIENT, $otp->getToken()));
    }

    #[Test]
    public function it_can_send_token_successfully(): void
    {
        Notification::fake();

        $this->assertTrue(OTP()->send(self::RECIPIENT));

        Notification::assertSentOnDemand(
            OTPNotification::class,
            fn ($notification, $channels, AnonymousNotifiable $notifiable) => $notifiable->routeNotificationFor('otp') === self::RECIPIENT
        );
    }

    #[Test]
    public function it_can_send_token_with_using_default_channel(): void
    {
        Notification::fake();

        $this->assertTrue(OTP()->send(self::RECIPIENT));

        Notification::assertSentOnDemand(
            OTPNotification::class,
            fn (OTPNotification $notification, $channels) => $channels[0] == config('otp.channel')
        );
    }

    #[Test]
    public function it_can_send_token_with_using_specified_channels(): void
    {
        Notification::fake();

        $useChannels = [CustomOTPChannel::class, 'mail'];
        $this->assertTrue(OTP()->channel($useChannels)->send(self::RECIPIENT));

        Notification::assertSentOnDemand(
            OTPNotification::class,
            fn (OTPNotification $notification, $channels) => $channels == $useChannels
        );
    }

    #[Test]
    public function it_can_set_channel_as_a_string(): void
    {
        Notification::fake();

        $this->assertTrue(OTP()->channel('mail')->send(self::RECIPIENT));

        Notification::assertSentOnDemand(
            OTPNotification::class,
            fn (OTPNotification $notification, $channels) => $channels === ['mail']
        );
    }

    #[Test]
    public function it_can_set_channel_as_an_array(): void
    {
        Notification::fake();

        $this->assertTrue(OTP()->channel([CustomOTPChannel::class, 'mail'])->send(self::RECIPIENT));

        Notification::assertSentOnDemand(
            OTPNotification::class,
            fn (OTPNotification $notification, $channels) => $channels === [CustomOTPChannel::class, 'mail']
        );
    }

    #[Test]
    public function it_routes_the_recipient_to_every_configured_channel(): void
    {
        Notification::fake();

        $this->assertTrue(OTP()->channel([CustomOTPChannel::class, 'mail'])->send(self::RECIPIENT));

        Notification::assertSentOnDemand(
            OTPNotification::class,
            fn ($notification, $channels, AnonymousNotifiable $notifiable) => $notifiable->routeNotificationFor(CustomOTPChannel::class) === self::RECIPIENT
                && $notifiable->routeNotificationFor('mail') === self::RECIPIENT
        );
    }

    #[Test]
    public function it_can_send_token_with_using_extended_channel(): void
    {
        Notification::fake();

        $this->assertTrue(OTP()->channel('otp_log')->send(self::RECIPIENT));

        Notification::assertSentOnDemand(
            OTPNotification::class,
            fn (OTPNotification $notification, $channels) => $channels == ['otp_log']
        );
    }

    #[Test]
    public function it_can_send_token_with_using_custom_channel(): void
    {
        Notification::fake();

        $this->assertTrue(OTP()->channel([CustomOTPChannel::class])->send(self::RECIPIENT));

        Notification::assertSentOnDemand(
            OTPNotification::class,
            fn (OTPNotification $notification, $channels) => $channels == [CustomOTPChannel::class]
        );
    }

    #[Test]
    public function it_can_not_validate_a_token_when_token_is_expired_or_invalid(): void
    {
        $this->expectException(OTPException::class);

        OTP()->validate(self::RECIPIENT, '12345');
    }

    #[Test]
    public function it_can_validate_a_valid_token(): void
    {
        Notification::fake();

        $otp = OTP();
        $otp->send(self::RECIPIENT);
        $this->assertTrue(OTP()->validate(self::RECIPIENT, $otp->getToken()));

        // Database Storage
        config()->set('otp.token_storage', 'database');
        $otp = OTP();
        $otp->send(self::RECIPIENT);
        $this->assertTrue(OTP()->validate(self::RECIPIENT, $otp->getToken()));
    }

    #[Test]
    public function it_can_revoke_a_token_successfully(): void
    {
        Notification::fake();

        OTP()->send(self::RECIPIENT);

        $this->assertTrue(OTP()->revoke(self::RECIPIENT));
        $this->assertFalse(OTP()->revoke(self::RECIPIENT));
    }

    #[Test]
    public function it_can_not_send_otp_when_already_sent(): void
    {
        $this->expectException(OTPException::class);

        Notification::fake();

        OTP()->send(self::RECIPIENT);

        OTP()->send(self::RECIPIENT);
    }

    #[Test]
    public function it_can_send_otp_with_custom_purpose(): void
    {
        Notification::fake();

        $purpose = 'customPurpose_';

        $otp = OTP()->purpose($purpose);
        $this->assertTrue($otp->send(self::RECIPIENT));
        $this->assertNotNull($otp->getToken());
    }

    #[Test]
    public function it_can_set_multiple_purposes(): void
    {
        Notification::fake();

        $firstPurpose = 'firstPurpose_';
        $secondPurpose = 'secondPurpose_';

        $firstOtp = OTP()->purpose($firstPurpose);
        $firstOtp->send(self::RECIPIENT);

        $secondOtp = OTP()->purpose($secondPurpose);
        $secondOtp->send(self::RECIPIENT);

        $defaultOtp = OTP();
        $defaultOtp->send(self::RECIPIENT);

        $this->assertNotNull($firstOtp->getToken());
        $this->assertNotNull($secondOtp->getToken());
        $this->assertNotNull($defaultOtp->getToken());

        $this->assertTrue(
            OTP()->purpose($secondPurpose)->validate(self::RECIPIENT, $secondOtp->getToken())
        );
    }

    #[Test]
    public function it_can_validate_token_with_custom_purpose(): void
    {
        Notification::fake();

        $purpose = 'customPurpose_';

        $otp = OTP()->purpose($purpose);
        $otp->send(self::RECIPIENT);

        $this->assertNotNull($otp->getToken());
        $this->assertTrue(OTP()->purpose($purpose)->validate(self::RECIPIENT, $otp->getToken()));
    }

    #[Test]
    public function it_cannot_validate_a_custom_purpose_token_without_the_same_purpose(): void
    {
        Notification::fake();

        $firstPurpose = 'firstPurpose_';

        $otp = OTP()->purpose($firstPurpose);
        $otp->send(self::RECIPIENT);

        $this->expectException(OTPException::class);

        OTP()->validate(self::RECIPIENT, $otp->getToken());
    }
}
