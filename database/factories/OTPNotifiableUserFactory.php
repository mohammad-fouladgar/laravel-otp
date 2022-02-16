<?php

namespace Fouladgar\OTP\Database\Factories;

use Fouladgar\OTP\Tests\Models\OTPNotifiableUser;
use Illuminate\Database\Eloquent\Factories\Factory;

class OTPNotifiableUserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = OTPNotifiableUser::class;

    /**
     * Define the model's default state.
     *
     */
    public function definition(): array
    {
        return [
            'mobile' => '+989389599530',
        ];
    }
}
