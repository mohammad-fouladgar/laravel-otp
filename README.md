# Laravel OTP (One-Time Password)

[![Latest Version on Packagist](https://img.shields.io/packagist/v/fouladgar/laravel-otp.svg)](https://packagist.org/packages/fouladgar/laravel-otp)
![Test Status](https://img.shields.io/github/actions/workflow/status/mohammad-fouladgar/laravel-otp/run-tests.yml?label=tests)
![Code Style Status](https://img.shields.io/github/actions/workflow/status/mohammad-fouladgar/laravel-otp/php-cs-fixer.yml?label=code%20style)
![Total Downloads](https://img.shields.io/packagist/dt/fouladgar/laravel-otp)

> **Upgrading from v5?** See the [Upgrade Guide](UPGRADE.md) for a step-by-step migration walkthrough.

## Introduction

Most web applications need an OTP (one-time password) or secure code to validate a user. This package only takes
care of generating, sending and validating OTP tokens for a **recipient** — a mobile number, an email address, or
any other identifier your notification channel knows how to deliver to. It does not manage or persist any "user"
model on your behalf. It's up to your application to decide what to do once a token is validated (e.g. login,
register, verify a phone number, etc.).

## Table of Contents

- [Basic Usage](#basic-usage)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
    - [Notification Channel](#notification-channel)
    - [Token Storage](#token-storage)
    - [Token Lifetime](#token-lifetime)
    - [Token Length](#token-length)
- [Purpose](#purpose)
- [Disabling Notifications](#disabling-notifications)
- [Customization](#customization)
- [Events](#events)
- [Translations](#translations)
- [Testing](#testing)
    - [Faking a Token in Your Application's Tests](#faking-a-token-in-your-applications-tests)
    - [Running Tests](#running-tests)

## Basic Usage

Once the package is configured, sending and validating tokens is straightforward. The same API works whether the
recipient is a mobile number, an email address, or another identifier supported by your notification channel.

**Send a token:**

```php
use Fouladgar\OTP\Facades\OTP;

OTP::send('+98900000000');
```

**Send to an email address** (point `otp.channel` to `mail`):

```php
OTP::channel('mail')->send('user@example.com');
```

**Send through multiple channels for this call:**

```php
OTP::channel(['mail', \App\Channels\CustomChannel::class])
    ->send('+98900000000');
```

**Validate a token** (recipient must match the value used for `send()`):

```php
OTP::validate('+98900000000', 'token_123'); // returns bool
```

**Read the generated token after `send()`:**

```php
use Fouladgar\OTP\Facades\OTP;

OTP::send('+98900000000');
$token = OTP::getToken();
```

**Revoke a pending token:**

```php
OTP::revoke('+98900000000');
```

> `send()` and `validate()` return `bool`. On failure, the package throws
> `Fouladgar\OTP\Exceptions\OTPException` instead of returning `false`.

Every channel receives the same recipient value. Only combine channels that understand the same recipient format.
For example, do not mix `mail` with SMS-only channels unless the recipient is an email address.

You can also inject `Fouladgar\OTP\OTPBroker` directly wherever you need it, such as in a controller or service
class. The `OTP()` helper is a shortcut for `app(OTPBroker::class)` and returns the broker directly — useful when
you need to chain calls without a static import.

## Requirements

- PHP `^8.2`
- Laravel `^10.0 | ^11.0 | ^12.0 | ^13.0`

## Installation

Install the package with Composer:

```shell
composer require fouladgar/laravel-otp
```

Thanks to Laravel's package auto-discovery, the service provider is registered automatically. No manual provider
registration is required.

## Configuration

Next, publish the configuration file. This creates `config/otp.php`, where you can choose the notification channel,
token storage driver, lifetime, and token length.

```
php artisan vendor:publish --provider="Fouladgar\OTP\ServiceProvider" --tag="config"
```

### Notification Channel

The package generates the token, builds a `Fouladgar\OTP\Notifications\OTPNotification`, and dispatches it through
the channel configured in `otp.channel`. The default channel is `otp_log`, which writes tokens to Laravel's log —
useful during development. **Replace it with your real channel before going to production.**

Write a standard Laravel notification channel and point `otp.channel` to it:

```php
<?php

namespace App\Channels;

use Fouladgar\OTP\Notifications\OTPNotification;
use Fouladgar\OTP\Notifications\Messages\OTPMessage;
use Fouladgar\OTP\Notifications\Messages\MessagePayload;

class MySMSChannel
{
    public function send($notifiable, OTPNotification $notification): void
    {
        $message = $notification->toSMS($notifiable);

        // $message->getPayload()->to()      → recipient
        // $message->getPayload()->content() → token message
    }
}
```

```php
// config/otp.php

return [
    // ...

    'channel' => \App\Channels\MySMSChannel::class,
];
```

> The recipient passed to `send()` is used as the routing target for every configured channel through Laravel's
> on-demand notifications. Built-in channels such as `mail` work too:
> `OTP::channel('mail')->send('info@example.com')`.

### Token Storage

The package stores generated tokens in either `cache` or `database`. The default driver is `cache`, which is usually
enough for simple applications.

You can change the driver in `config/otp.php`:

```php
// config/otp.php

return [
    /*
    |--------------------------------------------------------------------------
    | Supported drivers: "cache", "database"
    |--------------------------------------------------------------------------
    */
    'token_storage' => 'cache',
];
```

#### Cache

The `cache` driver uses your application's configured Laravel cache store. If your app does not have a suitable
cache store, use the `database` driver.

#### Database

The `database` driver stores tokens in a table created by the package migration.

Customize the table name if needed:

```php
// config/otp.php

return [
    'token_table' => 'otp_token',

    // ...
];
```

Then run the migrations:

```
php artisan migrate
```

### Token Lifetime

`token_lifetime` controls how long a pending token remains valid. While the current token has not expired, the
package will not generate or send another token for the same recipient and purpose.

This prevents users from requesting several active codes for the same flow at the same time.

```php
// config/otp.php

return [
    'token_lifetime' => env('OTP_TOKEN_LIFE_TIME', 2),

    // ...
];
```

### Token Length

`token_length` controls how many digits are generated for each token. The default value is `5`, but you can increase
or decrease it based on your application's verification requirements.

```php
// config/otp.php

return [
    // ...

    'token_length' => env('OTP_TOKEN_LENGTH', 5),

    // ...
];
```

## Purpose

Every token is generated for a `recipient` and a `purpose`. The purpose is a short scope tag that lets multiple OTP
flows exist for the same recipient without colliding.

For example, a user may request a login token and then request a password reset token before validating the first
one. With separate purposes, both tokens can exist independently for the same phone number.

```php
use Fouladgar\OTP\Facades\OTP;

OTP::purpose('login_')->send('+98900000000');
OTP::purpose('password_reset_')->send('+98900000000');

// This does not affect the still-pending "login_" token.
OTP::purpose('password_reset_')->validate('+98900000000', 'token_123');
```

- `validate()` and `revoke()` must use the same purpose that was used by `send()`.
- If you never call `purpose()`, the package uses the `otp.default_purpose` config value. The default is `otp_`.
- A pending token only blocks another `send()` when both the recipient and purpose are the same.

## Disabling Notifications

Use `withNotify(false)` when this package should generate the token but another part of your system should deliver
it. This is useful when SMS or email delivery is handled by a separate notification service.

In this mode, `send()` only generates and stores the token. No notification channel is required, and no notification
is dispatched.

```php
use Fouladgar\OTP\Facades\OTP;

OTP::withNotify(false)->send('98900000000');
```

To disable notifications globally for all calls, set `with_notify` to `false` in `config/otp.php`:

```php
'with_notify' => false,
```

After calling `send()`, you can read the token directly with `getToken()`. In a decoupled setup, listening for
`TokenCreated` is usually cleaner because a listener can publish the token to a queue or call your notification
service.

```php
<?php

use Fouladgar\OTP\Events\TokenCreated;
use Illuminate\Support\Facades\Event;

// e.g. in App\Providers\EventServiceProvider::boot()
Event::listen(TokenCreated::class, function (TokenCreated $event) {
    // publish to your notification microservice, e.g.:
    // NotificationServiceClient::send($event->recipient, $event->token);
});
```

`withNotify(false)` only affects `send()`. `validate()` and `revoke()` behave the same either way.

## Customization

### Notification Customization

You can customize the SMS and mail messages by registering closures in your
`App\Providers\AppServiceProvider::boot()`. Each closure receives the recipient and the generated token.

```php
<?php

use Fouladgar\OTP\Notifications\OTPNotification;
use Fouladgar\OTP\Notifications\Messages\OTPMessage;
use Illuminate\Notifications\Messages\MailMessage;

public function boot()
{
    OTPNotification::toSMSUsing(fn ($recipient, $token) => (new OTPMessage())
        ->to($recipient)
        ->content('Your OTP Token is: '.$token)
        ->template('OTP_TEMPLATE'));

    OTPNotification::toMailUsing(fn ($recipient, $token) => (new MailMessage)
        ->subject('OTP Request')
        ->line('Your OTP Token is: '.$token));
}
```

## Events

`OTPBroker` dispatches events throughout the token lifecycle. Listen for them with Laravel's normal event tools:
`Event::listen()`, listener classes, or `App\Providers\EventServiceProvider`.

Events are useful for audit logs, security monitoring, and decoupled notification delivery. For example, you can
listen for `TokenCreated` and publish the token to a queue or notification service.

### Token Events

#### `Fouladgar\OTP\Events\CreatingToken`

- **When:** before a token is generated
- **Payload:** `recipient`, `purpose`
- **Cancelable:** yes. If any listener returns `false`, token creation stops. No token is stored, no notification is
  sent, and `send()` returns `false`.

#### `Fouladgar\OTP\Events\TokenCreated`

- **When:** after a token is generated and stored
- **Payload:** `recipient`, `token`, `purpose`
- **Cancelable:** no

#### `Fouladgar\OTP\Events\TokenCreationFailed`

- **When:** `send()` is blocked because a pending token already exists for the same recipient and purpose
- **Payload:** `recipient`, `purpose`
- **Cancelable:** no

#### `Fouladgar\OTP\Events\TokenValidated`

- **When:** `validate()` succeeds, before the token is revoked
- **Payload:** `recipient`, `purpose`
- **Cancelable:** no

#### `Fouladgar\OTP\Events\TokenValidationFailed`

- **When:** `validate()` receives a missing, invalid, or expired token
- **Payload:** `recipient`, `purpose`
- **Cancelable:** no

#### `Fouladgar\OTP\Events\TokenRevoked`

- **When:** a pending token is deleted by `revoke()` or after a successful `validate()`
- **Payload:** `recipient`, `purpose`
- **Cancelable:** no

### Notification Events

#### `Fouladgar\OTP\Events\SendingNotification`

- **When:** before the built-in notification is dispatched
- **Payload:** `recipient`, `token`, `channels`
- **Cancelable:** yes. If any listener returns `false`, the token stays stored but the package does not send the
  notification for this call.
- **Skipped when:** notifications are disabled with `withNotify(false)`

#### `Fouladgar\OTP\Events\NotificationSent`

- **When:** after the built-in notification has been handed off
- **Payload:** `recipient`, `token`, `channels`
- **Cancelable:** no
- **Skipped when:** notifications are disabled with `withNotify(false)`

> `TokenCreationFailed` and `TokenValidationFailed` are useful security signals for repeated OTP requests or
> brute-force attempts. Missing channel configuration is not emitted as an event; it is reported immediately through
> `Fouladgar\OTP\Exceptions\OTPException`.

## Translations

Publish the translation file with:

```
php artisan vendor:publish --provider="Fouladgar\OTP\ServiceProvider" --tag="lang"
```

You can customize the provided language file:

```php
// resources/lang/vendor/OTP/en/otp.php

<?php

return [
    'otp_token' => 'Your OTP Token is: :token.',

    'otp_subject' => 'OTP request',
];
```

## Testing

### Faking a Token in Your Application's Tests

When testing a flow in your own application, such as an OTP login endpoint, you usually need a valid token without
actually sending SMS or email.

Use `OTP::fake()` instead of reaching into the cache or database directly. It stores a real, valid token using your
current configuration, including storage driver and purpose.

```php
<?php

use Fouladgar\OTP\Facades\OTP;

// Generate and store a random token, then use the returned value in your test.
$token = OTP::fake('98900000000');

// Or force a specific value if your test needs to assert on it:
$token = OTP::fake('98900000000', '12345');

// Pass a purpose directly if you are not using the default one.
$token = OTP::fake('98900000000', '12345', 'login_');

$this->postJson('/login-otp', [
    'mobile' => '98900000000',
    'token' => $token,
]);
```

> `fake()` skips the "already sent" check and does not dispatch events. It is only a test setup helper, not part of
> the normal `send()`/`validate()` flow.

### Running Tests

```sh
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security-related issues, please email fouladgar.dev@gmail.com instead of using the issue tracker.

## License

Laravel-OTP is released under the MIT License. See the bundled
[LICENSE](https://github.com/mohammad-fouladgar/laravel-otp/blob/master/LICENSE)
file for details.

Built with ❤️ for you.
