<?php

namespace Fouladgar\OTP\Tests;

use Fouladgar\OTP\Events\CreatingToken;
use Fouladgar\OTP\Events\NotificationSent;
use Fouladgar\OTP\Events\SendingNotification;
use Fouladgar\OTP\Events\TokenCreated;
use Fouladgar\OTP\Events\TokenCreationFailed;
use Fouladgar\OTP\Events\TokenRevoked;
use Fouladgar\OTP\Events\TokenValidated;
use Fouladgar\OTP\Events\TokenValidationFailed;
use Fouladgar\OTP\Exceptions\OTPException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;

class OTPEventsTest extends TestCase
{
    protected const RECIPIENT = '09389599530';

    #[Test]
    public function it_dispatches_events_on_successful_send(): void
    {
        Notification::fake();
        Event::fake();

        $this->assertTrue(OTP()->send(self::RECIPIENT));

        Event::assertDispatched(CreatingToken::class, fn ($event) => $event->recipient === self::RECIPIENT);
        Event::assertDispatched(TokenCreated::class, fn ($event) => $event->recipient === self::RECIPIENT && ! empty($event->token));
        Event::assertDispatched(SendingNotification::class, fn ($event) => $event->recipient === self::RECIPIENT);
        Event::assertDispatched(NotificationSent::class, fn ($event) => $event->recipient === self::RECIPIENT);
        Event::assertNotDispatched(TokenCreationFailed::class);
    }

    #[Test]
    public function it_dispatches_token_creation_failed_when_otp_already_sent(): void
    {
        Notification::fake();

        OTP()->send(self::RECIPIENT);

        Event::fake();

        $this->expectException(OTPException::class);

        try {
            OTP()->send(self::RECIPIENT);
        } finally {
            Event::assertDispatched(TokenCreationFailed::class);
            Event::assertNotDispatched(TokenCreated::class);
        }
    }

    #[Test]
    public function it_can_cancel_token_creation_via_a_listener(): void
    {
        Notification::fake();
        Event::listen(CreatingToken::class, fn () => false);

        $this->assertFalse(OTP()->send(self::RECIPIENT));

        Notification::assertNothingSent();
        $this->assertNull(Cache::get(self::RECIPIENT));
    }

    #[Test]
    public function it_can_skip_the_built_in_notification_via_a_listener(): void
    {
        Notification::fake();
        Event::listen(SendingNotification::class, fn () => false);

        $this->assertTrue(OTP()->send(self::RECIPIENT));

        Notification::assertNothingSent();
        $this->assertNotEmpty(Cache::get(self::RECIPIENT));
    }

    #[Test]
    public function it_dispatches_events_on_successful_validate(): void
    {
        Notification::fake();

        OTP()->send(self::RECIPIENT);
        $token = Cache::get(self::RECIPIENT)['token'];

        Event::fake();

        $this->assertTrue(OTP()->validate(self::RECIPIENT, $token));

        Event::assertDispatched(TokenValidated::class, fn ($event) => $event->recipient === self::RECIPIENT);
        Event::assertDispatched(TokenRevoked::class, fn ($event) => $event->recipient === self::RECIPIENT);
    }

    #[Test]
    public function it_dispatches_token_validation_failed_on_invalid_token(): void
    {
        Event::fake();

        $this->expectException(OTPException::class);

        try {
            OTP()->validate(self::RECIPIENT, 'invalid_token');
        } finally {
            Event::assertDispatched(TokenValidationFailed::class, fn ($event) => $event->recipient === self::RECIPIENT);
            Event::assertNotDispatched(TokenValidated::class);
        }
    }

    #[Test]
    public function it_dispatches_token_revoked_on_explicit_revoke(): void
    {
        Notification::fake();
        OTP()->send(self::RECIPIENT);

        Event::fake();

        $this->assertTrue(OTP()->revoke(self::RECIPIENT));

        Event::assertDispatched(TokenRevoked::class, fn ($event) => $event->recipient === self::RECIPIENT);
    }

    #[Test]
    public function it_does_not_dispatch_token_revoked_when_nothing_to_revoke(): void
    {
        Event::fake();

        $this->assertFalse(OTP()->revoke(self::RECIPIENT));

        Event::assertNotDispatched(TokenRevoked::class);
    }
}
