<?php

return [

    /*
     |--------------------------------------------------------------------------
     | Default OTP Tokens Table Name
     |--------------------------------------------------------------------------
     |
     | Here you should specify name of your OTP tokens table in database.
     | This table will held all information about created OTP tokens for users.
     |
     */

    'token_table' => 'otp_tokens',

    /*
     |--------------------------------------------------------------------------
     | Verification Token Length
     |--------------------------------------------------------------------------
     |
     | Here you can specify length of OTP tokens which will send to users.
     |
     */

    'token_length' => env('OTP_TOKEN_LENGTH', 5),

    /*
     |--------------------------------------------------------------------------
     | Verification Token Lifetime
     |--------------------------------------------------------------------------
     |
     | Here you can specify lifetime of OTP tokens (in minutes) which will send to users.
     |
     */

    'token_lifetime' => env('OTP_TOKEN_LIFE_TIME', 5),

    /*
    |--------------------------------------------------------------------------
    | OTP Prefix
    |--------------------------------------------------------------------------
    |
    | Here you can specify prefix of OTP tokens for adding to cache. This also doubles as the default
    | "purpose" used when you don't call ->purpose() explicitly.
    |
    */

    'prefix' => 'otp_',

    /*
     |--------------------------------------------------------------------------
     | SMS Client
     |--------------------------------------------------------------------------
     |
     | This package does not send SMS by itself. If you use the bundled "otp_sms" channel
     | (Fouladgar\OTP\Notifications\Channels\OTPSMSChannel), you must specify your implemented
     | "SMS Client" class here. This class is responsible for actually sending the SMS.
     |
     | If you use your own notification channel via "channel" below instead, this option is not needed at all.
     |
     */

    'sms_client' => '',

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

    'token_storage' => env('OTP_TOKEN_STORAGE', 'cache'),

    /*
    |--------------------------------------------------------------------------
    |  Notification Channel (REQUIRED)
    |--------------------------------------------------------------------------
    |
    | This package has no opinion on how an OTP token should be delivered, so there is no default channel.
    | You must specify the notification channel(s) that should be used, e.g. the bundled
    | "otp_sms" channel (Fouladgar\OTP\Notifications\Channels\OTPSMSChannel::class, which requires "sms_client"
    | above), your own custom channel class, or any Laravel/third-party notification channel.
    |
    */

    'channel' => null,

    /*
    |--------------------------------------------------------------------------
    | Send Notifications
    |--------------------------------------------------------------------------
    |
    | Determines whether OTP notifications should be sent by default.
    | Disable this when token delivery is handled by another service, such
    | as a notification service. This can be overridden at runtime
    | by calling the `withNotify()` method on the OTP broker.
    |
    */

    'with_notify' => true,
];