<?php

namespace Fouladgar\OTP\Tests;

use Fouladgar\OTP\Notifications\Channels\OTPSMSChannel;
use Fouladgar\OTP\Tests\Models\OTPNotifiableUser;
use Fouladgar\OTP\Token\OTPBroker;

class SampleTest extends TestCase
{
    /** @test * */
    public function it_test(): void
    {
//        $otpBroker = $this->app->make(OTPBroker::class);
        $user = new OTPNotifiableUser();
//        $user = $otpBroker->send($user->mobile);

        //        $otpBroker = $this->app->make(OTPBroker::class);
//        $user      = factory(OTPNotifiableUser::class)->create(['mobile'=>'09389599530']);
//        $user = $otpBroker->validate($user->mobile,'123456');
//
//        dd($user);

        /** @var OTPBroker $otpBroker */
        $otpBroker = $this->app->make(OTPBroker::class);
//        $otpBroker->send('09389599530'); // default is otp_sms.
//        $otpBroker->channel()->send('09389599530'); // default is mail.
        $otpBroker->channel([OTPSMSChannel::class, 'mail'])->send('09389599530');
//        $otpBroker->channel('otp_sms','mail')->send('09389599530');
//       dump($otpBroker->revoke($user));
    }


}
