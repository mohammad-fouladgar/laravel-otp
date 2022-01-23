<?php

namespace Fouladgar\OTP\Tests;

use Exception;
use Fouladgar\OTP\Contracts\NotifiableRepositoryInterface;
use Fouladgar\OTP\Contracts\OTPNotifiable;
use Fouladgar\OTP\UserProviderResolver;
use Illuminate\Config\Repository as Config;
use InvalidArgumentException;
use Mockery as m;
use stdClass;

class UserProviderResolverTest extends TestCase
{
    /** @test */
    public function it_can_throw_exception_if_provider_is_not_defined(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('User provider [undefined_provider] is not defined.');

        $config = m::mock(Config::class);

        $config->shouldReceive('get')->with('otp.user_providers.undefined_provider')->andReturn([]);

        $resolver = new UserProviderResolver($config);

        $resolver->resolve('undefined_provider');
    }

    /** @test * */
    public function it_can_throw_exception_if_model_is_not_an_valid_instance(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Your model must be an instance of "Fouladgar\OTP\Contracts\OTPNotifiable".');

        $config = m::mock(Config::class);

        $config->shouldReceive('get')->with('otp.user_providers.users')->andReturn([
            'model'      => stdClass::class,
            'repository' => stdClass::class,
        ]);

        $resolver = new UserProviderResolver($config);

        $resolver->resolve('users');
    }

    /** @test * */
    public function it_can_throw_exception_if_repository_is_not_an_valid_instance(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Your repository must implement "Fouladgar\OTP\Contracts\NotifiableRepositoryInterface".');

        $config = m::mock(Config::class);
        $model  = m::mock(OTPNotifiable::class);

        $config->shouldReceive('get')->with('otp.user_providers.users')->andReturn([
            'model'      => $model,
            'repository' => stdClass::class,
        ]);

        $resolver = new UserProviderResolver($config);

        $resolver->resolve('users');
    }

    /** @test * */
    public function it_can_resolve_provider_repository_successfully(): void
    {
        $config     = m::mock(Config::class);
        $model      = m::mock(OTPNotifiable::class);
        $repository = m::mock(NotifiableRepositoryInterface::class);

        $config->shouldReceive('get')->with('otp.user_providers.users')->andReturn([
            'model'      => $model,
            'repository' => $repository,
        ]);

        $resolver = new UserProviderResolver($config);

        $resolver->resolve('users');
    }

}
