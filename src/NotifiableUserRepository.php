<?php

namespace Fouladgar\OTP;

use Fouladgar\OTP\Contracts\OTPNotifiable;

class NotifiableUserRepository
{
    private OTPNotifiable $model;

    private string $mobileColumn;

    public function __construct()
    {
        $this->mobileColumn = config('otp.mobile_column');

        $modelClass  = config('otp.model');
        $this->model = new $modelClass();
    }

    public function findOrCreateByMobile(string $mobile): OTPNotifiable
    {
        return $this->model->firstOrCreate([$this->mobileColumn => $mobile]);
    }

    public function findByMobile(string $mobile): ?OTPNotifiable
    {
        return $this->model->where([$this->mobileColumn => $mobile])->first(['id', $this->mobileColumn]);
    }
}
