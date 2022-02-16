<?php

declare(strict_types=1);

namespace Fouladgar\OTP;

use Fouladgar\OTP\Contracts\SMSClient;
use Fouladgar\OTP\Contracts\TokenRepositoryInterface;
use Fouladgar\OTP\Exceptions\SMSClientNotFoundException;
use Fouladgar\OTP\Notifications\Channels\OTPSMSChannel;
use Fouladgar\OTP\Token\TokenRepositoryManager;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Throwable;

class ServiceProvider extends BaseServiceProvider
{
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

    public function register(): void
    {
        $this->mergeConfigFrom($this->getConfig(), 'otp');

        $this->registerBindings();
    }

    protected function loadAssetsFrom(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->loadTranslationsFrom(__DIR__.'/../lang', 'OTP');
    }

    protected function registerPublishing(): void
    {
        $this->publishes([$this->getConfig() => config_path('otp.php')], 'config');

        $this->publishes([__DIR__.'/../lang' => app()->langPath().'/vendor/OTP'], 'lang');

        $this->publishes([__DIR__.'/../database/migrations' => database_path('migrations')], 'migrations');
    }

    protected function getConfig(): string
    {
        return __DIR__.'/../config/config.php';
    }

    protected function registerBindings(): void
    {
        $this->app->singleton('token.repository', fn($app) => new TokenRepositoryManager($app));

        $this->app->singleton(TokenRepositoryInterface::class, fn($app) => $app['token.repository']->driver());

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
