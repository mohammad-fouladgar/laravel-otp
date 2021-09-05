<?php

return [

    /*
     |--------------------------------------------------------------------------
     | Default User Table Name
     |--------------------------------------------------------------------------
     |
     | Here you should specify name of your users table in database.
     |
     */
    'user_table'     => 'users',

    /*
     |--------------------------------------------------------------------------
     | Default Mobile Column
     |--------------------------------------------------------------------------
     |
     | Here you should specify name of your column (in users table) which user
     | mobile number reside in.
     |
     */
    'mobile_column'  => 'mobile',

    /*
     |--------------------------------------------------------------------------
     | Default OTPNotifiable model
     |--------------------------------------------------------------------------
     |
     | Here you should specify OTPNotifiable model. Keep in mind, this model must be
     | an instance of `Fouladgar\OTP\Contracts\OTPNotifiable` and also
     | use this `Fouladgar\OTP\Concerns\HasOTPNotify` trait.
     |
     */
    'model'          => App\Models\User::class,

    /*
     |--------------------------------------------------------------------------
     | Default OTP Tokens Table Name
     |--------------------------------------------------------------------------
     |
     | Here you should specify name of your OTP tokens table in database.
     | This table will held all information about created OTP tokens for users.
     |
     */
    'token_table'    => 'otp_tokens',

    /*
     |--------------------------------------------------------------------------
     | Verification Token Length
     |--------------------------------------------------------------------------
     |
     | Here you can specify length of OTP tokens which will send to users.
     |
     */
    'token_length'   => env('OTP_TOKEN_LENGTH', 5),

    /*
     |--------------------------------------------------------------------------
     | Verification Token Lifetime
     |--------------------------------------------------------------------------
     |
     | Here you can specify lifetime of OTP tokens (in minutes) which will send to users.
     |
     */
    'token_lifetime' => env('OTP_TOKEN_LENGTH', 5),

    /*
   |--------------------------------------------------------------------------
   | OTP Prefix
   |--------------------------------------------------------------------------
   |
   | Here you can specify prefix of OTP tokens for adding to cache.
   |
   */
    'prefix'         => 'otp_',

    /*
     |--------------------------------------------------------------------------
     | SMS Client (REQUIRED)
     |--------------------------------------------------------------------------
     |
     | Here you should specify your implemented "SMS Client" class. This class is
     | responsible for sending SMS to users.
     |
     */
    'sms_client'     => '',

    /*
    |--------------------------------------------------------------------------
    |  Token Storage Driver
    |--------------------------------------------------------------------------
    |
    | Here you may define token "storage" driver. If you choose the "cache", the token will be stored
    | in a cache driver configured by your application. Otherwise, a table will be created for storing tokens.
    |
    | Supported drivers: "cache", "database"
    |
    */
    'token_storage'  => env('OTP_TOKEN_STORAGE', 'cache'),

    /*
    |--------------------------------------------------------------------------
    |  Default SMS Notification Channel
    |--------------------------------------------------------------------------
    |
    |
    */
    'channel'        => \Fouladgar\OTP\Notifications\Channels\OTPSMSChannel::class,
];
