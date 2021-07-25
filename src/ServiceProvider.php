<?php

namespace Fouladgar\OTP;

use Fouladgar\OTP\Notifications\Channels\OTPChannel;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

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
                    'sms',
                    fn($app) => new OTPChannel() // todo:new sms client service
                );
            }
        );

        $this->loadAssetsFrom();

        $this->registerPublishing();
    }

    /**
     * Load and register package assets.
     */
    protected function loadAssetsFrom(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'OTP');
    }

    /**
     * Register the package's publishable resources.
     */
    protected function registerPublishing(): void
    {
        $this->publishes([$this->getConfig() => config_path('otp.php')], 'config');

        $this->publishes(
            [
                __DIR__ . '/../resources/lang' => resource_path('lang/vendor/OTP'),
            ],
            'lang'
        );

        $this->publishes([__DIR__ . '/../database/migrations' => database_path('migrations')], 'migrations');
    }

    /**
     * Get the config file path.
     */
    protected function getConfig(): string
    {
        return __DIR__ . '/../config/config.php';
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
     * Register any package bindings.
     */
    protected function registerBindings(): void
    {
    }
}
