<?php

declare(strict_types=1);

namespace Fouladgar\OTP\Tests;

use Fouladgar\OTP\ServiceProvider;
use Fouladgar\OTP\Tests\Models\OTPNotifiableUser;
use Orchestra\Testbench\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__.'/database/migrations');

        $this->withFactories(__DIR__.'/database/factories');
    }

    /**
     * @param  \Illuminate\Foundation\Application  $app
     */
    protected function getPackageProviders($app): array
    {
        return [ServiceProvider::class];
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     */
    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('otp.model', OTPNotifiableUser::class);
        $app['config']->set('otp.sms_client', SampleSMSClient::class);
        $app['config']->set('otp.prefix', '');
    }
}
