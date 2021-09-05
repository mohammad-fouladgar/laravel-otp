<?php

namespace Fouladgar\OTP;

use Fouladgar\OTP\Contracts\SMSClient;
use Fouladgar\OTP\Notifications\Channels\OTPSMSChannel;
use Fouladgar\OTP\Token\CacheTokenRepository;
use Fouladgar\OTP\Token\DatabaseTokenRepository;
use Fouladgar\OTP\Token\TokenRepositoryInterface;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\ConnectionInterface;
use Throwable;

class ServiceProvider extends BaseServiceProvider
{
    /**
     * Perform post-registration booting of services.
     */
    public function boot(): void
    {
        Notification::resolved(
            function (ChannelManager $service) {
                $service->extend(
                    'otp_sms',
                    fn($app) => new OTPSMSChannel($app->make(config('otp.sms_client')))
                );
            }
        );

        $this->loadAssetsFrom();

        $this->registerPublishing();
    }

    /**
     * Register any package services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom($this->getConfig(), 'otp');

        $this->registerBindings();
    }

    /**
     * Load and register package assets.
     */
    protected function loadAssetsFrom(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'OTP');
    }

    /**
     * Register the package's publishable resources.
     */
    protected function registerPublishing(): void
    {
        $this->publishes([$this->getConfig() => config_path('otp.php')], 'config');

        $this->publishes(
            [
                __DIR__.'/../resources/lang' => resource_path('lang/vendor/OTP'),
            ],
            'lang'
        );

        $this->publishes([__DIR__.'/../database/migrations' => database_path('migrations')], 'migrations');
    }

    /**
     * Get the config file path.
     */
    protected function getConfig(): string
    {
        return __DIR__.'/../config/config.php';
    }

    /**
     * Register any package bindings.
     */
    protected function registerBindings(): void
    {
        $this->app->bind(TokenRepositoryInterface::class, function ($app) {
            switch (config('otp.token_storage', 'cache')) {
                case 'cache':
                    return new CacheTokenRepository(
                        $app->make(CacheRepository::class),
                        config('otp.token_lifetime', 5),
                        config('otp.token_length', 5),
                        config('otp.prefix'),
                    );
                case 'database':
                    return new DatabaseTokenRepository(
                        $app->make(ConnectionInterface::class),
                        config('otp.token_lifetime'),
                        config('otp.token_length'),
                        config('otp.token_table'),
                    );
                default:
                    throw new \Exception('The Token storage is not supported.');
            }
        });

        $this->app->singleton(
            SMSClient::class,
            static function ($app) {
                try {
                    return $app->make(config('otp.sms_client'));
                } catch (Throwable $e) {
                    throw new SMSClientNotFoundException();
                }
            }
        );
    }
}
