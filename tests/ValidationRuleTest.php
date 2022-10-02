<?php

namespace Fouladgar\OTP\Tests;

use Fouladgar\OTP\Tests\Models\OTPNotifiableUser;
use Fouladgar\OTP\ValidationRule;
use Illuminate\Support\Facades\Cache;

class ValidationRuleTest extends TestCase
{
	public function setUp(): void
	{
		parent::setUp();
		config()->set('otp.user_providers.users.model', OTPNotifiableUser::class);
	}

	/**
	 * @test
	 */
	public function it_pass_valid_token_to_validation_rule(): void
	{
		$user = OTPNotifiableUser::factory()->create();

		OTP()->send($user->mobile);

		$this->assertTrue((new ValidationRule($user->mobile))->passes('otp', Cache::get($user->mobile)['token']));
	}

	/**
	 * @test
	 */
	public function it_pass_invalid_token_to_validation_rule(): void
	{
		$user = OTPNotifiableUser::factory()->create();

		OTP()->send($user->mobile);

		$this->assertFalse((new ValidationRule($user->mobile))->passes('otp', 'invalid-top'));
	}
}