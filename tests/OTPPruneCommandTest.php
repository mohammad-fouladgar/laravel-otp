<?php

namespace Fouladgar\OTP\Tests;

use Carbon\Carbon;
use Fouladgar\OTP\Contracts\TokenRepositoryInterface;
use PHPUnit\Framework\Attributes\Test;

class OTPPruneCommandTest extends TestCase
{
    protected string $recipient = '5555555555';

    protected string $purpose = 'otp_';

    #[Test]
    public function it_deletes_expired_tokens_from_the_database_driver(): void
    {
        config()->set('otp.token_storage', 'database');

        /** @var TokenRepositoryInterface $repository */
        $repository = $this->app->make(TokenRepositoryInterface::class);

        Carbon::setTestNow(Carbon::create(2022, 1, 20, 12));
        $repository->create($this->recipient, $this->purpose);
        Carbon::setTestNow();

        $repository->create('6666666666', $this->purpose);

        $this->artisan('otp:prune')->assertSuccessful();

        $this->assertDatabaseMissing('otp_tokens', ['recipient' => $this->recipient]);
        $this->assertDatabaseHas('otp_tokens', ['recipient' => '6666666666']);
    }

    #[Test]
    public function it_does_nothing_when_storage_driver_is_not_database(): void
    {
        config()->set('otp.token_storage', 'cache');

        $this->artisan('otp:prune')->assertSuccessful();
    }
}
