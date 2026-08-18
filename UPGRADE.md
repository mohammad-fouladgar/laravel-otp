# Upgrade Guide

## Upgrading from 5.x to 6.0

Version 6.0 contains several breaking changes. Read through each section below and update your application accordingly.

---

### 1. Run Migrations

Two database columns have been renamed. After updating the package, run:

```bash
php artisan migrate
```

- `otp_tokens.mobile` → `otp_tokens.recipient`
- `otp_tokens.indicator` → `otp_tokens.purpose`

> **Note:** If you are on Laravel 10 you must install `doctrine/dbal` before migrating:
> ```bash
> composer require doctrine/dbal
> ```
> Laravel 11+ does not require it.

---

### 2. User Model Management Removed

The package no longer manages or persists a user model. The following have been removed:

| Removed                         | Type                       |
|---------------------------------|----------------------------|
| `NotifiableRepository`          | Class                      |
| `NotifiableRepositoryInterface` | Interface                  |
| `OTPNotifiable`                 | Class                      |
| `HasOTPNotify`                  | Trait                      |
| `UserProviderResolver`          | Class                      |
| `useProvider()`                 | `OTPBroker` method         |
| `onlyConfirmToken()`            | `OTPBroker` method         |
| `user_providers`                | Config option              |
| `default_provider`              | Config option              |
| `mobile_column`                 | Config option              |

**Before (v5):**
```php
$user = OTP::useProvider('users')->send($mobile);
```

**After (v6):**

`send()` takes a plain string (the recipient) and returns `bool`. Finding or creating a user record is now your application's responsibility.

```php
$sent = OTP::send($mobile);

// After validating the token:
$valid = OTP::validate($mobile, $request->token);
if ($valid) {
    $user = User::firstOrCreate(['mobile' => $mobile]);
    Auth::login($user);
}
```

---

### 3. `otp.channel` Default Changed

The default channel is now `otp_log`, which logs tokens via Laravel's logger — useful for local development.
**Replace it with your real channel before going to production.**

**Before (v5):** the bundled `OTPSMSChannel` was the implicit default (required `sms_client` config).

**After (v6):** open `config/otp.php` and set your production channel:

```php
// Your own custom SMS/push channel
'channel' => \App\Notifications\Channels\MyChannel::class,

// Or a built-in Laravel channel
'channel' => 'mail',
```

---

### 4. `mobile` Renamed to `recipient`

The concept of `mobile` has been renamed to `recipient` throughout the package to reflect that the identifier can be a phone number, email address, or any other value a notification channel can route to.

**What changed:**
- `TokenRepositoryInterface` and `AbstractTokenRepository` method signatures now use `$recipient`
- The cache/database payload key is now `recipient`
- The `toSMSUsing()` and `toMailUsing()` closure argument is now `$recipient`

If you have a **custom `TokenRepository`**, update its method signatures:

```php
// Before
public function find(string $mobile, string $token): ?object { ... }
public function create(string $mobile, DateInterval $lifetime): string { ... }
public function delete(string $mobile): bool { ... }

// After
public function find(string $recipient, string $token): ?object { ... }
public function create(string $recipient, DateInterval $lifetime, ?string $token = null): string { ... }
public function delete(string $recipient): bool { ... }
```

---

### 5. `indicator()` Renamed to `purpose()`

`OTPBroker::indicator()` has been renamed to `purpose()`. Any event payload field named `indicator` is now `purpose`.

**Before (v5):**
```php
OTP::indicator('password_reset')->send($mobile);
```

**After (v6):**
```php
OTP::purpose('password_reset')->send($mobile);
```

---

### 6. New: `OTP` Facade

A first-class facade is now available. Add the alias to `config/app.php` if you prefer the short syntax:

```php
use Fouladgar\OTP\Facades\OTP;

OTP::send('09389599530');
```

---

### 7. New: `withNotify(false)` — Skip Notification Dispatch

Generates and stores a token without dispatching any notification. Useful when a separate service handles delivery:

```php
$sent = OTP::withNotify(false)->send($mobile);
```

This also means `otp.channel` does not need to be configured when using `withNotify(false)`.

You can also control this globally via the config:

```php
// config/otp.php
'with_notify' => false,
```

---

### 8. New: `fake()` for Testing

Stores a valid token for a recipient without sending anything, for use in tests:

```php
// Store a known token so you can submit it in a request
OTP::fake($mobile, '12345');

// Or let the package generate one and retrieve it
$token = OTP::fake($mobile);

// With a specific purpose
OTP::fake($mobile, '12345', 'password_reset');
```

---

### 9. New: Events

A full event lifecycle is now fired. Register listeners in your `EventServiceProvider` as needed:

| Event                                           | Cancelable |
|-------------------------------------------------|------------|
| `Fouladgar\OTP\Events\CreatingToken`            | Yes        |
| `Fouladgar\OTP\Events\SendingNotification`      | Yes        |
| `Fouladgar\OTP\Events\TokenCreated`             | No         |
| `Fouladgar\OTP\Events\TokenCreationFailed`      | No         |
| `Fouladgar\OTP\Events\NotificationSent`         | No         |
| `Fouladgar\OTP\Events\TokenValidated`           | No         |
| `Fouladgar\OTP\Events\TokenValidationFailed`    | No         |
| `Fouladgar\OTP\Events\TokenRevoked`             | No         |

Cancelable events can be aborted by returning `false` from a listener.

---

### 10. **Breaking:** `OTPSMSChannel` and `sms_client` Removed

The bundled `OTPSMSChannel` and the `SMSClient` contract have been removed. The package no longer ships a
dedicated SMS channel — use your own notification channel instead (see section 3).

Remove the following from your codebase:

| Removed                                                       | Replace with                          |
|---------------------------------------------------------------|---------------------------------------|
| `Fouladgar\OTP\Notifications\Channels\OTPSMSChannel`          | Your own channel class                |
| `Fouladgar\OTP\Contracts\SMSClient`                           | Not needed — inject your SMS SDK directly |
| `otp.sms_client` config key                                   | Remove from `config/otp.php`          |

**Before (v5):**
```php
// config/otp.php
'channel'    => \Fouladgar\OTP\Notifications\Channels\OTPSMSChannel::class,
'sms_client' => \App\MySMSClient::class,
```

**After (v6):**
```php
// App\Channels\MySMSChannel.php
class MySMSChannel
{
    public function __construct(private MySMSService $sms) {}

    public function send($notifiable, OTPNotification $notification): void
    {
        $message = $notification->toSMS($notifiable);
        $this->sms->send($message->getPayload()->to(), $message->getPayload()->content());
    }
}

// config/otp.php
'channel' => \App\Channels\MySMSChannel::class,
```

---

### 11. **Breaking:** `otp.prefix` Renamed to `otp.default_purpose`

The `prefix` config key has been renamed to `default_purpose` to clearly reflect what it actually does.

**Before (v5):**
```php
// config/otp.php
'prefix' => 'otp_',
```

**After (v6):**
```php
// config/otp.php
'default_purpose' => 'otp_',
```

Re-publish your config file or rename the key manually:

```bash
php artisan vendor:publish --provider="Fouladgar\OTP\ServiceProvider" --tag="config" --force
```

---

### 12. `OTP()` Helper Simplified

The `OTP()` helper no longer accepts arguments. It now only returns the broker instance.

**Before (v5):**
```php
OTP($mobile);               // send
OTP($mobile, $channels);    // send with channels
OTP($mobile, $token);       // validate
```

**After (v6):** use the Facade or the broker directly:
```php
use Fouladgar\OTP\Facades\OTP;

OTP::send($mobile);
OTP::channel($channels)->send($mobile);
OTP::validate($mobile, $token);

// Or via the helper (returns OTPBroker):
OTP()->send($mobile);
OTP()->channel($channels)->send($mobile);
OTP()->validate($mobile, $token);
```

---

### 13. New: `otp_log` Default Channel

Fresh installs now default to `otp_log` — a built-in channel that logs tokens via Laravel's logger. This means the package works out of the box with no channel configuration. **Replace it with your real channel before going to production.**

```php
// config/otp.php
'channel' => \App\Notifications\Channels\MyChannel::class, // or 'mail', etc.
```

---

### Summary Checklist

- [ ] Run `php artisan migrate`
- [ ] Remove `HasOTPNotify` trait from your User model (and any `NotifiableRepository` bindings)
- [ ] Remove `useProvider()` / `onlyConfirmToken()` calls; handle user lookup yourself after `validate()`
- [ ] Remove `otp.sms_client` config key and replace `OTPSMSChannel` with your own channel class
- [ ] Set `otp.channel` to your production channel in `config/otp.php`
- [ ] Replace `->indicator()` with `->purpose()`
- [ ] Rename `otp.prefix` to `otp.default_purpose` in `config/otp.php`
- [ ] Replace overloaded `OTP($mobile)` / `OTP($mobile, $token)` calls with `OTP()->send()` / `OTP()->validate()`
- [ ] Update custom `TokenRepository` method signatures (`mobile` → `recipient`)
- [ ] (Laravel 10 only) `composer require doctrine/dbal` before migrating
