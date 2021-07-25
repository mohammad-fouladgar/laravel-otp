<?php

namespace Fouladgar\OTP\Tests\Models;

use Fouladgar\OTP\Concerns\HasOTPNotify;
use Fouladgar\OTP\Contracts\OTPNotifiable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class OTPAbleUser extends Model implements OTPNotifiable
{
    use Notifiable;
    use HasOTPNotify;

    public $mobile = '09389599530';
}
