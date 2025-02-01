<?php

namespace Fouladgar\OTP\Tests;

use Carbon\Carbon;
use Fouladgar\OTP\Contracts\TokenRepositoryInterface;
use Fouladgar\OTP\Tests\Models\OTPNotifiableUser;
use Illuminate\Support\Facades\Cache;

class CacheTokenRepositoryTest extends TestCase
{
    protected TokenRepositoryInterface$repository;

    protected OTPNotifiableUser $user;

    public function setUp(): void
    {
        parent::setUp();

        config()->set('otp.token_storage', 'cache');
        $this->repository = $this->app->make(TokenRepositoryInterface::class);

        $this->user = new OTPNotifiableUser(['mobile' => '5555555555']);
        $this->indicator = 'otp_';
    }

    /**
     * @test
     */
    public function it_can_create_a_token_successfully(): void
    {
        $payload = ['mobile' => $this->user->mobile, 'indicator' => $this->indicator, 'sent_at' => now()->toDateTimeString()];
        $token = $this->repository->create($this->user, $this->indicator);
        $payload['token'] = $token;

        $signature = sprintf('%s%s', $payload['indicator'], $payload['mobile']);

        $this->assertEquals(Cache::get($signature), $payload);
    }

    /**
     * @test
     */
    public function it_can_delete_existing_token_successfully(): void
    {
        $this->repository->create($this->user, $this->indicator);

        $this->assertTrue($this->repository->deleteExisting($this->user, $this->indicator));

        $signature = sprintf('%s%s', $this->indicator, $this->user->mobile);

        $this->assertNull(Cache::get($signature));
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
}
