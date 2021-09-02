<?php

declare(strict_types=1);

namespace Fouladgar\OTP\Token;

use Fouladgar\OTP\Contracts\OTPNotifiable;
use Fouladgar\OTP\Exceptions\InvalidOTPTokenException;
use Fouladgar\OTP\NotifiableUserRepository;
use Illuminate\Support\Arr;

class OTPBroker
{
    private TokenRepositoryInterface $tokenRepository;

    private NotifiableUserRepository $userRepository;

    private array $channel;

    public function __construct(TokenRepositoryInterface $tokenRepository, NotifiableUserRepository $userRepository)
    {
        $this->tokenRepository = $tokenRepository;
        $this->userRepository  = $userRepository;

        $this->channel = $this->getDefaultChannel();
    }

    public function send(string $mobile): OTPNotifiable
    {
        /** @var OTPNotifiable $user */
        $user = $this->findOrCreateUser($mobile);

        $user->sendOTPNotification(
            $this->tokenRepository->create($user),
            $this->channel
        );

        return $user;
    }

    public function validate(string $mobile, string $token): OTPNotifiable
    {
        /** @var OTPNotifiable $user */
        $user = $this->findUserByMobile($mobile);

        throw_unless($this->tokenExists($user, $token), InvalidOTPTokenException::class);

        $this->tokenRepository->deleteExisting($user);

        return $user;
    }

    public function channel($channel = ['']): self
    {
        $this->channel = is_array($channel) ? $channel : func_get_args();

        return $this;
    }

    private function findOrCreateUser(string $mobile): OTPNotifiable
    {
        return $this->userRepository->findOrCreateByMobile($mobile);
    }

    private function findUserByMobile(string $mobile): ?OTPNotifiable
    {
        return $this->userRepository->findByMobile($mobile);
    }

    private function getDefaultChannel()
    {
        $channel = config('otp.channel');

        return is_array($channel) ? $channel : Arr::wrap($channel);
    }

    //    public function generate()
//    {
//    }
//
//    public function expiry(): self
//    {
//        return $this;
//    }
//
//    public function length(): self
//    {
//        return $this;
//    }

    private function tokenExists(OTPNotifiable $user, string $token): bool
    {
        return $this->tokenRepository->exists($user, $token);
    }
//
//    public function revoke()
//    {
//    }

}
