<?php

namespace Fouladgar\OTP\Tests;

use Fouladgar\OTP\Exceptions\OTPException;
use Fouladgar\OTP\Notifications\Channels\OTPSMSChannel;
use Fouladgar\OTP\Notifications\OTPNotification;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;

class OTPBrokerTest extends TestCase
{
    protected const RECIPIENT = '09389599530';

    #[Test]
    public function it_can_not_send_when_no_channel_is_configured(): void
    {
        config()->set('otp.channel', null);

        $this->expectException(OTPException::class);

        OTP()->send(self::RECIPIENT);
    }

    #[Test]
    public function it_can_send_without_notifying(): void
    {
        Notification::fake();
        config()->set('otp.channel', null);
        config()->set('otp.token_storage', 'cache');

        $this->assertTrue(OTP()->withNotify(false)->send(self::RECIPIENT));

        Notification::assertNothingSent();
        $signature = config('otp.prefix', 'otp_') . self::RECIPIENT;

        $this->assertNotEmpty(Cache::get($signature));
    }

    #[Test]
    public function it_can_validate_a_token_created_without_notifying(): void
    {
        OTP()->withNotify(false)->send(self::RECIPIENT);

        $this->assertTrue(OTP()->validate(self::RECIPIENT, Cache::get(self::RECIPIENT)['token']));
    }

    #[Test]
    public function it_can_send_token_successfully(): void
    {
        Notification::fake();

        $this->assertTrue(OTP(self::RECIPIENT));

        Notification::assertSentOnDemand(
            OTPNotification::class,
            fn($notification, $channels, AnonymousNotifiable $notifiable) => $notifiable->routeNotificationFor('otp') === self::RECIPIENT
        );
    }

    #[Test]
    public function it_can_send_token_with_using_default_channel(): void
    {
        Notification::fake();

        $this->assertTrue(OTP()->send(self::RECIPIENT));

        Notification::assertSentOnDemand(
            OTPNotification::class,
            fn(OTPNotification $notification, $channels) => $channels[0] == config('otp.channel')
        );
    }

    #[Test]
    public function it_can_send_token_with_using_specified_channels(): void
    {
        Notification::fake();

        $useChannels = [OTPSMSChannel::class, 'mail'];
        $this->assertTrue(OTP(self::RECIPIENT, $useChannels));

        Notification::assertSentOnDemand(
            OTPNotification::class,
            fn(OTPNotification $notification, $channels) => $channels == $useChannels
        );
    }

    #[Test]
    public function it_routes_the_recipient_to_every_configured_channel(): void
    {
        Notification::fake();

        $this->assertTrue(OTP(self::RECIPIENT, [OTPSMSChannel::class, 'mail']));

        Notification::assertSentOnDemand(
            OTPNotification::class,
            fn($notification, $channels, AnonymousNotifiable $notifiable) => $notifiable->routeNotificationFor(OTPSMSChannel::class) === self::RECIPIENT
                && $notifiable->routeNotificationFor('mail') === self::RECIPIENT
        );
    }

    #[Test]
    public function it_can_send_token_with_using_extended_channel(): void
    {
        Notification::fake();

        $this->assertTrue(OTP()->channel('otp_sms')->send(self::RECIPIENT));

        Notification::assertSentOnDemand(
            OTPNotification::class,
            fn(OTPNotification $notification, $channels) => $channels == ['otp_sms']
        );
    }

    #[Test]
    public function it_can_send_token_with_using_custom_channel(): void
    {
        Notification::fake();

        $this->assertTrue(OTP(self::RECIPIENT, [CustomOTPChannel::class]));

        Notification::assertSentOnDemand(
            OTPNotification::class,
            fn(OTPNotification $notification, $channels) => $channels == [CustomOTPChannel::class]
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

        OTP()->send(self::RECIPIENT);

        $this->assertTrue(OTP()->validate(self::RECIPIENT, Cache::get(self::RECIPIENT)['token']));

        // Database Storage
        config()->set('otp.token_storage', 'database');
        $otp = OTP();
        $otp->send(self::RECIPIENT);
        $this->assertTrue(OTP(self::RECIPIENT, $otp->getToken()));
    }

    #[Test]
    public function it_can_revoke_a_token_successfully(): void
    {
        Notification::fake();

        OTP(self::RECIPIENT);

        $this->assertTrue(OTP()->revoke(self::RECIPIENT));
        $this->assertFalse(OTP()->revoke(self::RECIPIENT));
    }

    #[Test]
    public function it_can_not_send_otp_when_already_sent(): void
    {
        $this->expectException(OTPException::class);

        Notification::fake();

        OTP(self::RECIPIENT);

        OTP()->send(self::RECIPIENT);
    }

    #[Test]
    public function it_can_send_otp_with_custom_purpose(): void
    {
        Notification::fake();

        $purpose = 'customPurpose_';

        $this->assertTrue(OTP()->purpose($purpose)->send(self::RECIPIENT));

        $this->assertNotEmpty(Cache::get($purpose . self::RECIPIENT));
    }

    #[Test]
    public function it_can_set_multiple_purposes(): void
    {
        Notification::fake();

        $firstPurpose = 'firstPurpose_';
        $secondPurpose = 'secondPurpose_';

        OTP()->purpose($firstPurpose)->send(self::RECIPIENT);
        OTP()->purpose($secondPurpose)->send(self::RECIPIENT);
        OTP()->send(self::RECIPIENT);

        $this->assertNotEmpty(Cache::get($firstPurpose . self::RECIPIENT));
        $this->assertNotEmpty(Cache::get($secondPurpose . self::RECIPIENT));
        $this->assertNotEmpty(Cache::get(self::RECIPIENT));

        $this->assertTrue(
            OTP()->purpose($secondPurpose)->validate(
                self::RECIPIENT,
                Cache::get($secondPurpose . self::RECIPIENT)['token']
            )
        );
    }

    #[Test]
    public function it_can_validate_token_with_custom_purpose(): void
    {
        Notification::fake();

        $purpose = 'customPurpose_';

        OTP()->purpose($purpose)->send(self::RECIPIENT);

        $token = Cache::get($purpose . self::RECIPIENT)['token'];

        $this->assertNotEmpty(Cache::get($purpose . self::RECIPIENT));

        $this->assertTrue(OTP()->purpose($purpose)->validate(self::RECIPIENT, $token));
    }

    #[Test]
    public function it_can_not_validate_without_custom_purpose(): void
    {
        Notification::fake();

        $firstPurpose = 'firstPurpose_';

        OTP()->purpose($firstPurpose)->send(self::RECIPIENT);

        $this->assertNotEmpty(Cache::get($firstPurpose . self::RECIPIENT));

        $token = Cache::get($firstPurpose . self::RECIPIENT)['token'];

        $this->expectException(OTPException::class);

        OTP()->validate(self::RECIPIENT, $token);
    }
}