<?php

declare(strict_types=1);

namespace Fouladgar\OTP;

use Fouladgar\OTP\Contracts\TokenRepositoryInterface;
use Fouladgar\OTP\Events\CreatingToken;
use Fouladgar\OTP\Events\NotificationSent;
use Fouladgar\OTP\Events\SendingNotification;
use Fouladgar\OTP\Events\TokenCreated;
use Fouladgar\OTP\Events\TokenCreationFailed;
use Fouladgar\OTP\Events\TokenRevoked;
use Fouladgar\OTP\Events\TokenValidated;
use Fouladgar\OTP\Events\TokenValidationFailed;
use Fouladgar\OTP\Events\TooManyValidationAttempts;
use Fouladgar\OTP\Exceptions\OTPException;
use Fouladgar\OTP\Notifications\OTPNotification;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Timebox;
use Throwable;

class OTPBroker
{
    private array $channel;

    private string $purpose;

    private ?string $token = null;

    private ?bool $withNotify = null;

    public function __construct(private readonly TokenRepositoryInterface $tokenRepository)
    {
        $this->channel = $this->getDefaultChannel();
        $this->purpose = $this->getDefaultPurpose();
    }

    /**
     * @throws Throwable
     */
    public function send(string $recipient): bool
    {
        throw_if($this->shouldNotify() && empty($this->channel), OTPException::whenChannelIsNotConfigured());

        if ($this->tokenExists($recipient)) {
            event(new TokenCreationFailed($recipient, $this->purpose));

            throw OTPException::whenOtpAlreadySent();
        }

        if (event(new CreatingToken($recipient, $this->purpose), [], true) === false) {
            return false;
        }

        $this->token = $this->tokenRepository->create($recipient, $this->purpose);

        event(new TokenCreated($recipient, $this->token, $this->purpose));

        if ($this->shouldNotify()) {
            $this->sendNotification($recipient, $this->token);
        }

        return true;
    }

    /**
     * @throws OTPException|Throwable
     */
    public function validate(string $recipient, string $token): bool
    {
        return (new Timebox())->call(function (Timebox $timebox) use ($recipient, $token) {
            if ($this->tooManyAttempts($recipient)) {
                event(new TooManyValidationAttempts($recipient, $this->purpose));

                throw OTPException::whenTooManyAttempts();
            }

            if (! $this->isTokenMatching($recipient, $token)) {
                event(new TokenValidationFailed($recipient, $this->purpose));

                throw OTPException::whenOtpTokenIsInvalid();
            }

            $this->clearAttempts($recipient);

            event(new TokenValidated($recipient, $this->purpose));

            $this->revoke($recipient);

            $timebox->returnEarly();

            return true;
        }, (int) config('otp.validation_timebox_microseconds', 200_000));
    }

    public function withNotify(bool $withNotify = true): static
    {
        $this->withNotify = $withNotify;

        return $this;
    }

    public function fake(string $recipient, ?string $token = null, ?string $purpose = null): string
    {
        return $this->token = $this->tokenRepository->create($recipient, $purpose ?? $this->purpose, $token);
    }

    public function purpose(string $purpose): static
    {
        $this->purpose = $purpose;

        return $this;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function channel(array|string $channel): static
    {
        $this->channel = (array) $channel;

        return $this;
    }

    public function revoke(string $recipient): bool
    {
        $revoked = $this->tokenRepository->deleteExisting($recipient, $this->purpose);

        if ($revoked) {
            event(new TokenRevoked($recipient, $this->purpose));
        }

        return $revoked;
    }

    private function isTokenMatching(string $recipient, string $token): bool
    {
        return $this->tokenRepository->isTokenMatching($recipient, $this->purpose, $token);
    }

    private function tooManyAttempts(string $recipient): bool
    {
        $maxAttempts = config('otp.rate_limit.max_attempts');

        if (empty($maxAttempts)) {
            return false;
        }

        return ! RateLimiter::attempt(
            $this->rateLimiterKey($recipient),
            (int) $maxAttempts,
            static fn () => true,
            (int) config('otp.rate_limit.decay_seconds', 60)
        );
    }

    private function clearAttempts(string $recipient): void
    {
        RateLimiter::clear($this->rateLimiterKey($recipient));
    }

    private function rateLimiterKey(string $recipient): string
    {
        return sprintf('otp-validate:%s%s', $this->purpose, $recipient);
    }

    private function sendNotification(string $recipient, string $token): void
    {
        if (event(new SendingNotification($recipient, $token, $this->channel), [], true) === false) {
            return;
        }

        $this->notify($recipient, $token);

        event(new NotificationSent($recipient, $token, $this->channel));
    }

    private function notify(string $recipient, string $token): void
    {
        $notifiable = new AnonymousNotifiable();

        foreach ($this->channel as $channel) {
            $notifiable->route($channel, $recipient);
        }

        $notifiable
            ->route('otp', $recipient)
            ->notify(new OTPNotification($recipient, $token, $this->channel));
    }

    private function getDefaultChannel(): array
    {
        $channel = config('otp.channel');

        return is_array($channel) ? $channel : Arr::wrap($channel);
    }

    private function tokenExists(string $recipient): bool
    {
        return $this->tokenRepository->exists($recipient, $this->purpose);
    }

    private function getDefaultPurpose(): string
    {
        return config('otp.default_purpose', 'otp_');
    }

    private function shouldNotify(): bool
    {
        return $this->withNotify ?? config('otp.with_notify', true);
    }
}
