<?php

declare(strict_types=1);

namespace Fouladgar\OTP\Tests;

use Fouladgar\OTP\ServiceProvider;
use Illuminate\Foundation\Application;
use Orchestra\Testbench\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
    }

    /**
     * @param  Application  $app
     */
    protected function getPackageProviders($app): array
    {
        return [ServiceProvider::class];
    }

    /**
     * Define environment setup.
     *
     * @param  Application  $app
     */
    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('otp.channel', 'otp_log');
        $app['config']->set('otp.default_purpose', '');
    }
}
