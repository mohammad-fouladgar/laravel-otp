<?php

namespace Fouladgar\OTP\Tests;

use Carbon\Carbon;
use Fouladgar\OTP\Contracts\TokenRepositoryInterface;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;

class DatabaseTokenRepositoryTest extends TestCase
{
    protected TokenRepositoryInterface $repository;

    protected string $recipient = '5555555555';

    protected string $purpose = 'otp_';

    public function setUp(): void
    {
        parent::setUp();

        $config = app('config');
        $config->set('otp.token_storage', 'database');
        $this->repository = $this->app->make(TokenRepositoryInterface::class);
    }

    #[Test]
    public function it_can_create_a_token_successfully(): void
    {
        $token = $this->repository->create($this->recipient, $this->purpose);

        $this->assertEquals(config('otp.token_length'), Str::length($token));

        $this->assertDatabaseHas('otp_tokens', [
            'recipient' => $this->recipient,
            'token' => $token,
            'purpose' => $this->purpose,
            'expires_at' => (string)now()->addMinutes(config('otp.token_lifetime')),
        ]);
    }

    #[Test]
    public function it_can_delete_existing_token_successfully(): void
    {
        $token = $this->repository->create($this->recipient, $this->purpose);

        $tokenRow = [
            'recipient' => $this->recipient,
            'token' => $token,
            'purpose' => $this->purpose,
        ];

        $this->assertTrue($this->repository->deleteExisting($this->recipient, $this->purpose));
        $this->assertDatabaseMissing('otp_tokens', $tokenRow);
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
        $this->assertFalse($this->repository->isTokenMatching($this->recipient, $this->purpose, $token));
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

        $this->assertDatabaseHas('otp_tokens', [
            'recipient' => $this->recipient,
            'token' => '12345',
            'purpose' => $this->purpose,
        ]);
    }

    #[Test]
    public function it_sets_the_correct_purpose_in_the_database_record(): void
    {
        $customPurpose = 'custom_';

        $token = $this->repository->create($this->recipient, $customPurpose);

        $this->assertDatabaseHas('otp_tokens', [
            'recipient' => $this->recipient,
            'token' => $token,
            'purpose' => $customPurpose,
        ]);
    }
}
