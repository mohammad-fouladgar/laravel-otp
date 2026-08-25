<?php

return [

    /*
    |--------------------------------------------------------------------------
    | OTP Language file
    |--------------------------------------------------------------------------
    |
    | The following language lines are the default messages.
    |
    */
    'otp_token' => 'Your OTP Token is: :token.',

    'otp_subject' => 'OTP request',

    'token_has_been_expired_or_invalid' => 'the token has been expired or invalid',

    'otp_has_already_been_sent' => 'OTP has already been sent',

    'channel_is_not_configured' => 'No notification channel is configured. Set "otp.channel" in your config file or call ->channel() before sending.',

    'too_many_attempts' => 'Too many attempts. Please try again later.',
];
