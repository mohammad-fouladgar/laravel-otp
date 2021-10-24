<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use Faker\Generator as Faker;
use Fouladgar\OTP\Tests\Models\OTPNotifiableUser;

$factory->define(OTPNotifiableUser::class, fn(Faker $faker) => [
    'mobile' => '555555',
]);


