<?php

declare(strict_types=1);

namespace Fouladgar\OTP;

use Exception;
use Fouladgar\OTP\Contracts\NotifiableRepositoryInterface;
use Fouladgar\OTP\Contracts\OTPNotifiable;
use Fouladgar\OTP\Contracts\TokenRepositoryInterface;
use Fouladgar\OTP\Exceptions\InvalidOTPTokenException;
use Fouladgar\OTP\Exceptions\UserNotFoundByMobileException;
use Illuminate\Support\Arr;
use Throwable;

class OTPBroker
{
    /**
     * @var TokenRepositoryInterface
     */
    private $tokenRepository;

    /**
     * @var array
     */
    private $channel;

    /**
     * @var string|null
     */
    private $token = null;

    /**
     * @var UserProviderResolver
     */
    private $providerResolver;

    /** @var NotifiableRepositoryInterface */
    private $userRepository;

    public function __construct(TokenRepositoryInterface $tokenRepository, UserProviderResolver $providerResolver)
    {
        $this->tokenRepository  = $tokenRepository;
        $this->providerResolver = $providerResolver;
        $this->channel          = $this->getDefaultChannel();
        $this->userRepository   = $this->resolveUserRepository();
    }

    /**
     * @throws Throwable
     */
    public function send(string $mobile, bool $userExists = false): OTPNotifiable
    {
        $user = $userExists ? $this->findUserByMobile($mobile) : null;

        throw_if(!$user && $userExists, UserNotFoundByMobileException::class);

        $notifiable = $user ?? $this->makeNotifiable($mobile);

        $this->token = $this->tokenRepository->create($notifiable);

        $notifiable->sendOTPNotification(
            $this->token,
            $this->channel
        );

        return $notifiable;
    }

    /**
     * @throws InvalidOTPTokenException|Throwable
     */
    public function validate(string $mobile, string $token, bool $create = true): OTPNotifiable
    {
        $notifiable = $this->makeNotifiable($mobile);

        throw_unless($this->tokenExists($notifiable, $token), InvalidOTPTokenException::class);

        $notifiable = $this->find($mobile, $create);

        $this->revoke($notifiable);

        return $notifiable;
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

    public function channel($channel = ['']): self
    {
        $this->channel = is_array($channel) ? $channel : func_get_args();

        return $this;
    }

    public function revoke(OTPNotifiable $user): bool
    {
        return $this->tokenRepository->deleteExisting($user);
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
        return $this->userRepository->findByMobile($mobile);
    }

    private function getDefaultChannel(): array
    {
        $channel = config('otp.channel');

        return is_array($channel) ? $channel : Arr::wrap($channel);
    }

    private function tokenExists(OTPNotifiable $user, string $token): bool
    {
        return $this->tokenRepository->exists($user, $token);
    }

    private function makeNotifiable(string $mobile): OTPNotifiable
    {
        return $this->userRepository->getModel()->make(['mobile' => $mobile]);
    }
}
