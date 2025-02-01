<?php

declare(strict_types=1);

namespace Fouladgar\OTP;

use Exception;
use Fouladgar\OTP\Contracts\NotifiableRepositoryInterface;
use Fouladgar\OTP\Contracts\OTPNotifiable;
use Fouladgar\OTP\Contracts\TokenRepositoryInterface;
use Fouladgar\OTP\Exceptions\OTPException;
use Illuminate\Support\Arr;
use Throwable;

class OTPBroker
{
    private array $channel;

    private string $indicator;

    private ?string $token = null;

    private bool $onlyConfirm = false;

    private NotifiableRepositoryInterface $userRepository;

    public function __construct(
        private TokenRepositoryInterface $tokenRepository,
        private UserProviderResolver $providerResolver
    ) {
        $this->channel = $this->getDefaultChannel();
        $this->indicator = $this->getDefaultIndicator();
        $this->userRepository = $this->resolveUserRepository();
    }

    /**
     * @throws Throwable
     */
    public function send(string $mobile, bool $userExists = false): OTPNotifiable
    {
        $user = $userExists ? $this->findUserByMobile($mobile) : null;

        throw_if(! $user && $userExists, OTPException::whenUserNotFoundByMobile());
        throw_if($this->tokenExists($mobile), OTPException::whenOtpAlreadySent());

        $notifiable = $user ?? $this->makeNotifiable($mobile);

        $this->token = $this->tokenRepository->create($notifiable, $this->indicator);

        $notifiable->sendOTPNotification(
            $this->token,
            $this->channel
        );

        return $notifiable;
    }

    /**
     * @throws OTPException|Throwable
     */
    public function validate(string $mobile, string $token, bool $create = true): OTPNotifiable
    {
        $notifiable = $this->makeNotifiable($mobile);

        throw_unless($this->verifyToken($notifiable, $token), OTPException::whenOtpTokenIsInvalid());

        if (! $this->onlyConfirm) {
            $notifiable = $this->find($mobile, $create);
        }

        $this->revoke($notifiable);

        return $notifiable;
    }

    public function onlyConfirmToken(bool $confirm = true): static
    {
        $this->onlyConfirm = $confirm;

        return $this;
    }

    public function indicator(string $indicator): static
    {
        $this->indicator = $indicator;

        return $this;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    /**
     * @throws Exception
     */
    public function useProvider(string $name = null): OTPBroker
    {
        $this->userRepository = $this->resolveUserRepository($name);

        return $this;
    }

    public function channel($channel = ['']): static
    {
        $this->channel = is_array($channel) ? $channel : func_get_args();

        return $this;
    }

    public function revoke(OTPNotifiable $user): bool
    {
        return $this->tokenRepository->deleteExisting($user, $this->indicator);
    }

    /**
     * @throws \Exception
     */
    protected function resolveUserRepository(string $name = null): NotifiableRepositoryInterface
    {
        return $this->providerResolver->resolve($name);
    }

    private function find(string $mobile, bool $create = true): ?OTPNotifiable
    {
        return $create ?
            $this->findOrCreateUser($mobile) :
            $this->findUserByMobile($mobile);
    }

    private function findOrCreateUser(string $mobile): OTPNotifiable
    {
        return $this->userRepository->findOrCreateByMobile($mobile);
    }

    private function findUserByMobile(string $mobile): ?OTPNotifiable
    {
        return $this->userRepository->findByMobile($mobile, $this->indicator);
    }

    private function getDefaultChannel(): array
    {
        $channel = config('otp.channel');

        return is_array($channel) ? $channel : Arr::wrap($channel);
    }

    public function verifyToken(OTPNotifiable $user, string $token): bool
    {
        return $this->tokenRepository->isTokenMatching($user, $this->indicator, $token);
    }

    private function tokenExists(string $mobile): bool
    {
        return $this->tokenRepository->exists($mobile, $this->indicator);
    }

    private function makeNotifiable(string $mobile): OTPNotifiable
    {
        $mobileColumn = config('otp.mobile_column');

        return $this->userRepository->getModel()->make([$mobileColumn => $mobile]);
    }

    private function getDefaultIndicator()
    {
        return config('otp.prefix');
    }
}
