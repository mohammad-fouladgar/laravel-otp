<?php

return [

    /*
     |--------------------------------------------------------------------------
     | Default OTP Tokens Table Name
     |--------------------------------------------------------------------------
     |
     | Here you should specify name of your OTP tokens table in database.
     | This table will hold all information about created OTP tokens for users.
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
     | Token Generator
     |--------------------------------------------------------------------------
     |
     | The class responsible for generating a token's value. The default generator produces
     | numeric-only tokens.
     |
     */

    'token_generator' => Fouladgar\OTP\Token\Generators\NumericAbstractTokenGenerator::class,

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
    | Default Purpose
    |--------------------------------------------------------------------------
    |
    | The purpose used when you don't call ->purpose() explicitly. It scopes the token
    | to a specific flow (e.g. "login_", "password_reset_") so multiple independent OTPs
    | can exist for the same recipient at once.
    |
    */

    'default_purpose' => 'otp_',

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
    |  Notification Channel
    |--------------------------------------------------------------------------
    |
    | The notification channel used to deliver OTP tokens. The bundled "otp_log" channel
    | logs tokens via Laravel's logger — useful for local development and testing without
    | any SMS or email setup. Replace it with your real channel before going to production:
    |
    |   "mail"     → Laravel's built-in mail channel
    |   Any custom channel class you implement yourself
    |
    */

    'channel' => 'otp_log',

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

    /*
    |--------------------------------------------------------------------------
    | Validation Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Limits how many times validate() may be attempted for the same recipient/purpose within
    | the given time window, to slow down brute-force guessing. Set "max_attempts" to null (or 0)
    | to disable rate limiting entirely.
    |
    */

    'rate_limit' => [
        'max_attempts'  => env('OTP_RATE_LIMIT_MAX_ATTEMPTS', 5),
        'decay_seconds' => env('OTP_RATE_LIMIT_DECAY_SECONDS', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation Timebox
    |--------------------------------------------------------------------------
    |
    | A failed validate() call is padded to take at least this many microseconds, so an
    | attacker can't use response timing to tell apart an invalid token, an expired one, or a
    | rate-limited attempt. Successful validations are never delayed. Set to 0 to disable.
    |
    */

    'validation_timebox_microseconds' => env('OTP_VALIDATION_TIMEBOX_MICROSECONDS', 200_000),
];
