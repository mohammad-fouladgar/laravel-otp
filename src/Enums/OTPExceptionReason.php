<?php

declare(strict_types=1);

namespace Fouladgar\OTP\Enums;

enum OTPExceptionReason: string
{
    case ChannelNotConfigured = 'channel_not_configured';
    case AlreadySent = 'already_sent';
    case InvalidToken = 'invalid_token';
    case TooManyAttempts = 'too_many_attempts';
}
