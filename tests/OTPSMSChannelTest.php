<?php

namespace Fouladgar\OTP\Tests;

use Fouladgar\OTP\Contracts\SMSClient;
use Fouladgar\OTP\Notifications\Channels\OTPSMSChannel;
use Fouladgar\OTP\Notifications\Messages\MessagePayload;
use Fouladgar\OTP\Notifications\Messages\OTPMessage;
use Fouladgar\OTP\Notifications\OTPNotification;
use Fouladgar\OTP\Tests\Models\OTPNotifiableUser;
use Mockery as m;

class OTPSMSChannelTest extends TestCase
{
    /** @test */
    public function it_can_successfully_send_token(): void
    {
        $notifiable     = m::mock(OTPNotifiableUser::class);
        $notification   = m::mock(OTPNotification::class);
        $messagePayload = m::mock(MessagePayload::class);
        $OTPMessage     = m::mock(OTPMessage::class);
        $SMSClient      = m::mock(SMSClient::class);

        $OTPMessage->shouldReceive('getPayload')->andReturn($messagePayload);
        $notifiable->shouldReceive('routeNotificationFor')->with('otp', $notification)->andReturnTrue();
        $notification->shouldReceive('toSMS')->with($notifiable)->andReturn($OTPMessage);

        $SMSClient->shouldReceive('sendMessage')->with($messagePayload)->andReturn(true);

        $OTPSMSChannel = new OTPSMSChannel($SMSClient);

        $this->assertTrue($OTPSMSChannel->send($notifiable, $notification));
    }

    /** @test */
    public function it_does_not_work_when_there_is_no_otp_route_notification(): void
    {
        $notifiable   = m::mock(OTPNotifiableUser::class);
        $notification = m::mock(OTPNotification::class);
        $SMSClient    = m::mock(SMSClient::class);

        $notifiable->shouldReceive('routeNotificationFor')
                   ->with('otp', $notification)
                   ->andReturnFalse();

        $OTPSMSChannel = new OTPSMSChannel($SMSClient);

        $this->assertNull($OTPSMSChannel->send($notifiable, $notification));
    }
}
