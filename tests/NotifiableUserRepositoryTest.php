<?php

namespace Fouladgar\OTP\Tests;

use Fouladgar\OTP\Contracts\OTPNotifiable;
use Fouladgar\OTP\NotifiableUserRepository;
use Fouladgar\OTP\Tests\Models\OTPNotifiableUser;

class NotifiableUserRepositoryTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_not_find_user_by_mobile_if_does_not_exist(): void
    {
        $repository = new NotifiableUserRepository();

        $this->assertNull($repository->findByMobile('09389599530'));
    }

    /**
     * @test
     */
    public function it_can_find_user_by_mobile_if_exists(): void
    {
        $user       = factory(OTPNotifiableUser::class)->create();
        $repository = new NotifiableUserRepository();

        $this->assertInstanceOf(OTPNotifiable::class, $repository->findByMobile($user->mobile));
    }

    /**
     * @test
     */
    public function it_can_create_user_by_mobile_if_does_not_exist(): void
    {
        $repository = new NotifiableUserRepository();

        $this->assertInstanceOf(OTPNotifiable::class, $repository->findOrCreateByMobile('09389599530'));
    }

    /**
     * @test
     */
    public function it_can_find_user_by_mobile_if_exists_instead_creating(): void
    {
        $user       = factory(OTPNotifiableUser::class)->create();
        $repository = new NotifiableUserRepository();

        $this->assertInstanceOf(OTPNotifiable::class, $repository->findOrCreateByMobile($user->mobile));
    }


}

