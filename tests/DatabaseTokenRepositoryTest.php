<?php

namespace Fouladgar\OTP\Tests;

use Carbon\Carbon;
use Fouladgar\OTP\Contracts\TokenRepositoryInterface;
use Fouladgar\OTP\Tests\Models\OTPNotifiableUser;
use Illuminate\Support\Str;

class DatabaseTokenRepositoryTest extends TestCase
{
    protected TokenRepositoryInterface $repository;

    protected OTPNotifiableUser $user;

    public function setUp(): void
    {
        parent::setUp();

        $config = app('config');
        $config->set('otp.token_storage', 'database');
        $this->repository = $this->app->make(TokenRepositoryInterface::class);

        $this->user = new OTPNotifiableUser(['mobile' => '5555555555']);
        $this->indicator = 'otp_';
    }

    /**
     * @test
     */
    public function it_can_create_a_token_successfully(): void
    {
        $token = $this->repository->create($this->user, $this->indicator);

        $this->assertEquals(config('otp.token_length'), Str::length($token));

        $this->assertDatabaseHas('otp_tokens', [
            'mobile' => $this->user->mobile,
            'token' => $token,
            'indicator' => $this->indicator,
            'expires_at' => (string)now()->addMinutes(config('otp.token_lifetime')),
        ]);
    }

    /**
     * @test
     */
    public function it_can_delete_existing_token_successfully(): void
    {
        $token = $this->repository->create($this->user, $this->indicator);

        $tokenRow = [
            'mobile' => $this->user->mobile,
            'token' => $token,
            'indicator' => $this->indicator,
        ];

        $this->assertTrue($this->repository->deleteExisting($this->user, $this->indicator));
        $this->assertDatabaseMissing('otp_tokens', $tokenRow);
    }

    /**
     * @test
     */
    public function it_can_find_existing_and_not_expired_token_successfully(): void
    {
        $token = $this->repository->create($this->user, $this->indicator);

        $this->assertTrue($this->repository->isTokenMatching($this->user, $this->indicator, $token));
    }

    /**
     * @test
     */
    public function it_fails_when_token_is_exist_but_expired(): void
    {
        $testDate = Carbon::create(2022, 1, 20, 12);
        Carbon::setTestNow($testDate);

        $this->repository = $this->app->make(TokenRepositoryInterface::class);
        $token = $this->repository->create($this->user, $this->indicator);

        Carbon::setTestNow();
        $this->assertFalse($this->repository->exists($this->user, $this->indicator, $token));
    }

    /**
     * @test
     */
    public function it_fails_when_token_does_not_exists(): void
    {
        $this->repository->create($this->user, $this->indicator);

        $this->assertFalse($this->repository->exists($this->user, $this->indicator, 'invalid_token'));
    }

    /**
     * @test
     */
    public function it_sets_the_correct_indicator_in_the_database_record(): void
    {
        $customIndicator = 'custom_';

        $token = $this->repository->create($this->user, $customIndicator);

        $this->assertDatabaseHas('otp_tokens', [
            'mobile' => $this->user->mobile,
            'token' => $token,
            'indicator' => $customIndicator,
        ]);
    }
}
