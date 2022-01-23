<?php

namespace Fouladgar\OTP;

use Fouladgar\OTP\Contracts\NotifiableRepositoryInterface;
use Fouladgar\OTP\Contracts\OTPNotifiable;

class NotifiableRepository implements NotifiableRepositoryInterface
{
    /**
     * @var OTPNotifiable
     */
    protected $model;

    /**
     * @var string
     */
    protected $mobileColumn;

    public function __construct(OTPNotifiable $model)
    {
        $this->mobileColumn = config('otp.mobile_column');

        $this->model = $model;
    }

    public function findOrCreateByMobile(string $mobile): OTPNotifiable
    {
        return $this->model->firstOrCreate([$this->mobileColumn => $mobile]);
    }

    public function findByMobile(string $mobile): ?OTPNotifiable
    {
        return $this->model->where([$this->mobileColumn => $mobile])->first(['id', $this->mobileColumn]);
    }


    public function getModel(): OTPNotifiable
    {
        return $this->model;
    }
}
