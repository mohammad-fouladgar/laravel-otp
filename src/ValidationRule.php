<?php

namespace Fouladgar\OTP;

use Fouladgar\OTP\Tests\Models\OTPNotifiableUser;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Lang;

class ValidationRule implements \Illuminate\Contracts\Validation\Rule
{
	private string $mobile;

	public function __construct(string $mobile)
	{
		$this->mobile = $mobile;
	}

	/**
	 * @inheritDoc
	 */
	public function passes($attribute, $value): bool
	{
		try
		{
			if (OTP()->validate($this->mobile, $value) instanceof OTPNotifiableUser)
			{
				return true;
			}
		}
		catch (\Exception $exception)
		{
			return false;
		}

		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function message()
	{
		return Lang::get('validation.custom.otp.invalid');
	}
}