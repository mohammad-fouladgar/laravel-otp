<?php

namespace Fouladgar\OTP\Tests;

use Fouladgar\OTP\Tests\Models\OTPNotifiableUser;
use Fouladgar\OTP\Token\TokenRepositoryInterface;
use Illuminate\Support\Str;

class DatabaseTokenRepositoryTest extends TestCase
{
    /**
     * @var TokenRepositoryInterface|mixed
     */
    protected $repository;

    /**
     * @var OTPNotifiableUser
     */
    protected $user;

    public function setUp(): void
    {
        parent::setUp();

        $config = app('config');
        $config->set('otp.token_storage', 'database');
        $this->repository = $this->app->make(TokenRepositoryInterface::class);

        $this->user = new OTPNotifiableUser(['mobile' => '5555555555']);
    }

    /**
     * @test
     */
    public function it_can_create_a_token_successfully(): void
    {
        $token = $this->repository->create($this->user);

        $this->assertEquals(config('otp.token_length'), Str::length($token));

        $this->assertDatabaseHas('otp_tokens', [
            'mobile'     => $this->user->mobile,
            'token'      => $token,
            'expires_at' => (string) now()->addMinutes(config('otp.token_lifetime')),
        ]);
    }

    /**
     * @test
     */
    public function it_can_delete_existing_token_successfully(): void
    {
        $token = $this->repository->create($this->user);

        $tokenRow = [
            'mobile' => $this->user->mobile,
            'token'  => $token,
        ];

        $this->assertTrue($this->repository->deleteExisting($this->user));
        $this->assertDatabaseMissing('otp_tokens', $tokenRow);
    }

    /**
     * @test
     */
    public function it_can_find_existing_and_not_expired_token_successfully(): void
    {
        $token = $this->repository->create($this->user);

        $this->assertTrue($this->repository->exists($this->user, $token));
    }

    /**
     * @test
     */
    public function it_fails_when_token_is_exist_but_expired(): void
    {
        config()->set('otp.token_lifetime', -5);

        $this->repository = $this->app->make(TokenRepositoryInterface::class);
        $token            = $this->repository->create($this->user);

        $this->assertFalse($this->repository->exists($this->user, $token));
    }

    /**
     * @test
     */
    public function it_fails_when_token_does_not_exists(): void
    {
        $this->repository->create($this->user);

        $this->assertFalse($this->repository->exists($this->user, 'invalid_token'));
    }
}
