<?php

return [

    /*
    |--------------------------------------------------------------------------
    | OTP Default Provider
    |--------------------------------------------------------------------------
    |
    | This option controls the default otp "userProvider" for your application.
    | You may change this option, but it's a perfect start fot most applications.
    |
    */
    'default_provider' => 'users',

    /*
     |--------------------------------------------------------------------------
     | User Providers
     |--------------------------------------------------------------------------
     |
     | Here you should specify your user providers. This defines how the users are actually retrieved out of your
     | database or other storage mechanisms used by this application to persist your user's data.
     |
     | Keep in mind, every model must implement "Fouladgar\OTP\Contracts\OTPNotifiable" and also
     | use this "Fouladgar\OTP\Concerns\HasOTPNotify" trait.
     |
     | You may also change the default repository and replace your own repository. But every repository must
     | implement "Fouladgar\OTP\Contracts\NotifiableRepositoryInterface" interface.
     |
     */
    'user_providers'   => [
        'users' => [
            'table'      => 'users',
            'model'      => \App\Models\User::class, // if Laravel < 8, change it to \App\User::class
            'repository' => \Fouladgar\OTP\NotifiableRepository::class,
        ],

//        'admins' => [
//            'model'      => \App\Models\Admin::class,
//            'repository' => \Fouladgar\OTP\NotifiableRepository::class,
//        ],
    ],

    /*
     |--------------------------------------------------------------------------
     | Default Mobile Column
     |--------------------------------------------------------------------------
     |
     | Here you should specify name of your column (in users table) which user
     | mobile number reside in.
     |
     */
    'mobile_column'    => 'mobile',

    /*
     |--------------------------------------------------------------------------
     | Default OTP Tokens Table Name
     |--------------------------------------------------------------------------
     |
     | Here you should specify name of your OTP tokens table in database.
     | This table will held all information about created OTP tokens for users.
     |
     */
    'token_table'      => 'otp_tokens',

    /*
     |--------------------------------------------------------------------------
     | Verification Token Length
     |--------------------------------------------------------------------------
     |
     | Here you can specify length of OTP tokens which will send to users.
     |
     */
    'token_length'     => env('OTP_TOKEN_LENGTH', 5),

    /*
     |--------------------------------------------------------------------------
     | Verification Token Lifetime
     |--------------------------------------------------------------------------
     |
     | Here you can specify lifetime of OTP tokens (in minutes) which will send to users.
     |
     */
    'token_lifetime'   => env('OTP_TOKEN_LENGTH', 5),

    /*
   |--------------------------------------------------------------------------
   | OTP Prefix
   |--------------------------------------------------------------------------
   |
   | Here you can specify prefix of OTP tokens for adding to cache.
   |
   */
    'prefix'           => 'otp_',

    /*
     |--------------------------------------------------------------------------
     | SMS Client (REQUIRED)
     |--------------------------------------------------------------------------
     |
     | Here you should specify your implemented "SMS Client" class. This class is responsible
     | for sending SMS to users. You may use your own sms channel, so this is not a required option anymore.
     |
     */
    'sms_client'       => '',

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
    'token_storage'    => env('OTP_TOKEN_STORAGE', 'cache'),

    /*
    |--------------------------------------------------------------------------
    |  Default SMS Notification Channel
    |--------------------------------------------------------------------------
    |
    | This is an otp default sms channel. But you may specify your own sms channel.
    | If you use default channel you must set "sms_client". Otherwise you don't need that.
    |
    */
    'channel'          => \Fouladgar\OTP\Notifications\Channels\OTPSMSChannel::class,
];
