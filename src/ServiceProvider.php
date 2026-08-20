<?php

declare(strict_types=1);

namespace Fouladgar\OTP;

use Fouladgar\OTP\Console\Commands\PruneExpiredTokens;
use Fouladgar\OTP\Contracts\AbstractTokenGenerator;
use Fouladgar\OTP\Contracts\TokenRepositoryInterface;
use Fouladgar\OTP\Notifications\Channels\OTPLogChannel;
use Fouladgar\OTP\Token\Generators\NumericAbstractTokenGenerator;
use Fouladgar\OTP\Token\TokenRepositoryManager;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    public function boot(): void
    {
        Notification::resolved(function (ChannelManager $service) {
            $service->extend(
                'otp_log',
                fn ($app) => new OTPLogChannel($app->make('log'))
            );
        });

        $this->loadAssetsFrom();

        $this->registerPublishing();

        $this->registerCommands();
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
        $this->app->singleton(
            AbstractTokenGenerator::class,
            fn ($app) => $app->make($app['config']->get('otp.token_generator', NumericAbstractTokenGenerator::class))
        );

        $this->app->singleton('token.repository', fn ($app) => new TokenRepositoryManager($app));

        $this->app->singleton(TokenRepositoryInterface::class, fn ($app) => $app['token.repository']->driver());
    }

    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([PruneExpiredTokens::class]);
        }
    }

}
