<?php

declare(strict_types=1);

namespace Fouladgar\OTP\Token;

use Fouladgar\OTP\Contracts\OTPNotifiable;
use Fouladgar\OTP\Exceptions\InvalidOTPTokenException;
use Fouladgar\OTP\Exceptions\UserNotFoundByMobileException;
use Fouladgar\OTP\NotifiableUserRepository;
use Fouladgar\OTP\Tests\Models\OTPNotifiableUser;
use Illuminate\Support\Arr;
use Throwable;

class OTPBroker
{
    /**
     * @var TokenRepositoryInterface
     */
    private $tokenRepository;

    /**
     * @var NotifiableUserRepository
     */
    private $userRepository;

    /**
     * @var array
     */
    private $channel;

    /**
     * @var string|null
     */
    private $token = null;

    public function __construct(TokenRepositoryInterface $tokenRepository, NotifiableUserRepository $userRepository)
    {
        $this->tokenRepository = $tokenRepository;
        $this->userRepository  = $userRepository;

        $this->channel = $this->getDefaultChannel();
    }

    public function send(string $mobile): OTPNotifiable
    {
        $user = $this->findOrCreateUser($mobile);

        $this->token = $this->tokenRepository->create($user);

        $user->sendOTPNotification(
            $this->token,
            $this->channel
        );

        return $user;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    /**
     * @throws InvalidOTPTokenException|Throwable
     */
    public function validate(string $mobile, string $token): OTPNotifiable
    {
        $user = $this->findUserByMobile($mobile);

        throw_unless($user, UserNotFoundByMobileException::class);

        throw_unless($this->tokenExists($user, $token), InvalidOTPTokenException::class);

        $this->revoke($user);

        return $user;
    }

    public function channel($channel = ['']): self
    {
        $this->channel = is_array($channel) ? $channel : func_get_args();

        return $this;
    }

    public function revoke(OTPNotifiableUser $user): bool
    {
        return $this->tokenRepository->deleteExisting($user);
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
}