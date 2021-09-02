<?php

namespace Fouladgar\OTP;

use Fouladgar\OTP\Contracts\OTPNotifiable;
use Fouladgar\OTP\Exceptions\InvalidNotifiableUserException;
use Throwable;

class NotifiableUserRepository
{
    private OTPNotifiable $modelInstance;

    private string $mobileColumn;

    /**
     * @throws Throwable
     */
    public function __construct()
    {
        $this->mobileColumn = config('otp.mobile_column');

        $modelClass          = config('otp.model');
        $this->modelInstance = new $modelClass();
        throw_if(!$this->modelInstance instanceof OTPNotifiable, InvalidNotifiableUserException::class);
    }

    public function findOrCreateByMobile(string $mobile): OTPNotifiable
    {
        return $this->modelInstance->firstOrCreate([$this->mobileColumn => $mobile]);
    }

    public function findByMobile(string $mobile)
    {
        return $this->modelInstance->where([$this->mobileColumn => $mobile])->first(['id', $this->mobileColumn]);
    }
}
