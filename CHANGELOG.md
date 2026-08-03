## 6.0.0 - Unreleased
- **Breaking:** the package no longer manages or persists a "user" model. `NotifiableRepository`,
  `NotifiableRepositoryInterface`, `OTPNotifiable`, `HasOTPNotify`, and `UserProviderResolver` have been removed,
  along with the `user_providers`/`default_provider`/`mobile_column` config options and `useProvider()`/
  `onlyConfirmToken()` methods. `OTPBroker::send()` and `OTPBroker::validate()` now take/return a plain mobile
  string and a `bool` — finding or creating a user record after validation is now the application's responsibility.
- Add `Fouladgar\OTP\Facades\OTP` facade (e.g. `OTP::send('09389599530')`).
- **Breaking:** `otp.channel` no longer defaults to the bundled `OTPSMSChannel`. The package has no opinion on
  delivery, so you must explicitly configure `otp.channel` (either the bundled channel + `sms_client`, or your own
  notification channel). Calling `send()` without a configured channel now throws `OTPException` instead of
  implicitly requiring `sms_client`.
- **Breaking:** renamed the `mobile` concept to `recipient` throughout the package, since a recipient can be a
  mobile number, an email address, or any other identifier a notification channel can deliver to. This affects the
  `TokenRepositoryInterface`/`AbstractTokenRepository` method signatures, the cache payload key, and the
  `toSMSUsing`/`toMailUsing` closure argument. A new migration renames the existing `otp_tokens.mobile` database
  column to `recipient` (the original `2021_11_02_000000_create_otp_tokens_table` migration itself was left
  untouched since it has already run in previous versions — just run `php artisan migrate` to pick up the new one).
  Renaming a column on Laravel 10 requires `doctrine/dbal`; not needed on Laravel 11+.
- Fixed: sending now routes the recipient to *every* configured channel (previously only the bundled `otp_sms`
  channel's own `'otp'` route key was set), so built-in Laravel channels like `mail` actually receive the recipient
  and deliver instead of silently doing nothing.
- Add `OTPBroker::withoutNotify()` — generates and stores the token without dispatching any notification (and
  without requiring `otp.channel` to be configured), for setups where a separate service (e.g. a "notification"
  microservice) is responsible for actual delivery.
- Add event-driven lifecycle hooks under `Fouladgar\OTP\Events`: `CreatingToken` and `SendingNotification` (both
  cancelable — return `false` from a listener to abort), `TokenCreated`, `TokenCreationFailed`, `NotificationSent`,
  `TokenValidated`, `TokenValidationFailed`, and `TokenRevoked`. See the README's "Events" section.
- **Breaking:** renamed `indicator` to `purpose` throughout the package (`OTPBroker::indicator()` → `purpose()`, the
  events' `indicator` payload field → `purpose`) — "purpose" is a clearer name for what this value actually does:
  scoping a token to what it's for (e.g. `login_` vs. `password_reset_`) so multiple independent OTPs can exist for
  the same recipient at once. The `otp.prefix` config option is unrelated to this rename and keeps its name — it
  still doubles as the default `purpose` value, same as before. A new migration renames the existing
  `otp_tokens.indicator` database column to `purpose` (the original
  `2025_01_28_000000_add_indicator_to_otp_tokens_table` migration was left untouched since it has already run in
  previous versions — just run `php artisan migrate` to pick up the new one).
- Add `OTPBroker::fake()` — stores a valid token for a recipient without sending anything, for use in your own
  application's tests (e.g. exercising an OTP login endpoint without an actual SMS/email). Optionally accepts a
  specific token value and/or purpose (`fake($recipient, $token = null, $purpose = null)`) instead of requiring
  `->purpose()` first. `TokenRepositoryInterface::create()` gained a matching optional `$token` parameter.

## 5.7.1 - 2026-06-26
- support L13

## 5.7.0 - 2026-05-02
- L13 support

## 5.6.0 - 2025-04-16
- Add support Laravel 13

## 5.5.0 - 2025-04-16
- Add support Laravel 12

## 5.3.0 - 2025-04-14
- Add support template for `OTPMessage`

## 5.2.0 - 2025-02-01
- Add support custom indicator for managing multiple OTP tokens

## 5.1.0 - 2025-01-27
- Fix exception message handling

## 5.0.0 - 2025-01-26
- Add support otp lifetime token functionality with expiration handling
- Replace `InvalidOTPTokenException` and `UserNotFoundByMobileException` with `OTPException` for better error handling

## 4.3.0 - 2024-06-21
- Add support only confirm token

## 4.2.0 - 2024-04-12
- Add support Laravel 11
- Detracted Laravel 9

## 4.0.0 - 2023-03-29
- Add support for Laravel V10

## 3.0.1 - 2022-11-10
- Fix database expire token logic

## 3.0.0 - 2022-02-16
- Add Support PHP 8.0 and Laravel 9.0
- Deprecated PHP <= 7.* and Laravel <=8.*

## 1.0.0 - 2022-02-16
- Init (Support PHP 8.0, 7.4, 7.3 and Laravel 8.*, 7.*, 6.*)