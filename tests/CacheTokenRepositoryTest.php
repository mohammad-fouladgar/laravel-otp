<?php

namespace Fouladgar\OTP\Tests;

use Carbon\Carbon;
use Fouladgar\OTP\Contracts\TokenRepositoryInterface;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;

class CacheTokenRepositoryTest extends TestCase
{
    protected TokenRepositoryInterface $repository;

    protected string $recipient = '5555555555';

    protected string $purpose = 'otp_';

    public function setUp(): void
    {
        parent::setUp();

        config()->set('otp.token_storage', 'cache');
        $this->repository = $this->app->make(TokenRepositoryInterface::class);
    }

    #[Test]
    public function it_can_create_a_token_successfully(): void
    {
        $payload = ['recipient' => $this->recipient, 'purpose' => $this->purpose, 'sent_at' => now()->toDateTimeString()];
        $token = $this->repository->create($this->recipient, $this->purpose);
        $payload['token'] = $token;

        $signature = sprintf('%s%s', $payload['purpose'], $payload['recipient']);

        $this->assertEquals(Cache::get($signature), $payload);
    }

    #[Test]
    public function it_can_delete_existing_token_successfully(): void
    {
        $this->repository->create($this->recipient, $this->purpose);

        $this->assertTrue($this->repository->deleteExisting($this->recipient, $this->purpose));

        $signature = sprintf('%s%s', $this->purpose, $this->recipient);

        $this->assertNull(Cache::get($signature));
    }

    #[Test]
    public function it_can_find_existing_and_not_expired_token_successfully(): void
    {
        $token = $this->repository->create($this->recipient, $this->purpose);

        $this->assertTrue($this->repository->isTokenMatching($this->recipient, $this->purpose, $token));
    }

    #[Test]
    public function it_fails_when_token_is_exist_but_expired(): void
    {
        $testDate = Carbon::create(2022, 1, 20, 12);
        Carbon::setTestNow($testDate);

        $this->repository = $this->app->make(TokenRepositoryInterface::class);

        $token = $this->repository->create($this->recipient, $this->purpose);

        Carbon::setTestNow();
        $this->assertFalse($this->repository->exists($this->recipient, $this->purpose));
    }

    #[Test]
    public function it_fails_when_token_does_not_exists(): void
    {
        $this->repository->create($this->recipient, $this->purpose);

        $this->assertFalse($this->repository->isTokenMatching($this->recipient, $this->purpose, 'invalid_token'));
    }

    #[Test]
    public function it_can_create_a_token_with_a_forced_value(): void
    {
        $token = $this->repository->create($this->recipient, $this->purpose, '12345');

        $this->assertSame('12345', $token);
        $this->assertTrue($this->repository->isTokenMatching($this->recipient, $this->purpose, '12345'));
    }
}
