# Laravel OTP (One-Time Password)

[![Latest Version on Packagist](https://img.shields.io/packagist/v/fouladgar/laravel-otp.svg)](https://packagist.org/packages/fouladgar/laravel-otp)
![Test Status](https://img.shields.io/github/actions/workflow/status/mohammad-fouladgar/laravel-otp/run-tests.yml?label=tests)
![Code Style Status](https://img.shields.io/github/actions/workflow/status/mohammad-fouladgar/laravel-otp/php-cs-fixer.yml?label=code%20style)
![Total Downloads](https://img.shields.io/packagist/dt/fouladgar/laravel-otp)

## Introduction

Most web applications need an OTP (one-time password) or secure code to validate a user. This package only takes
care of generating, sending and validating OTP tokens for a **recipient** — a mobile number, an email address, or
any other identifier your notification channel knows how to deliver to. It does not manage or persist any "user"
model on your behalf. It's up to your application to decide what to do once a token is validated (e.g. login,
register, verify a phone number, etc.).

## Table of Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
    - [Notification Channel (REQUIRED)](#notification-channel-required)
    - [SMS Client](#sms-client)
    - [Token Storage](#token-storage)
    - [Token Life Time](#token-life-time)
- [Basic Usage](#basic-usage)
- [Purpose](#purpose)
- [Without Notify](#without-notify)
- [Customization](#customization)
- [Events](#events)
- [Translates](#translates)
- [Testing](#testing)
    - [Faking a Token in Your Application's Tests](#faking-a-token-in-your-applications-tests)

## Requirements

- PHP `^8.2`
- Laravel `^10.0 | ^11.0 | ^12.0 | ^13.0`

## Installation

You can install the package via composer:

```shell
composer require fouladgar/laravel-otp
```

Thanks to Laravel's package auto-discovery, the service provider is registered automatically — no manual step needed.

## Configuration

As next step, let's publish the config file `config/otp.php` by executing:

```
php artisan vendor:publish --provider="Fouladgar\OTP\ServiceProvider" --tag="config"
```

### Notification Channel (REQUIRED)

This package doesn't ship with a default delivery mechanism — it just dispatches a `Fouladgar\OTP\Notifications\OTPNotification`.
You must tell it which notification channel(s) to use via `otp.channel`. You have two options:

**Option A — use the bundled SMS channel.** This requires you to also configure `sms_client` (see [SMS Client](#sms-client)):

```php
//config/otp.php
<?php

return [
    // ...

    'channel' => \Fouladgar\OTP\Notifications\Channels\OTPSMSChannel::class,
];
```

**Option B — use your own (or a third-party) notification channel.** Nothing SMS-specific is required from this
package at all; write a plain channel class the way [Laravel's notification system](https://laravel.com/docs/notifications#creating-the-channel)
expects, and point `otp.channel` to it:

```php
<?php

namespace App\Channels;

use Fouladgar\OTP\Notifications\OTPNotification;

class CustomChannel
{
    public function send($notifiable, OTPNotification $notification): void
    {
        $message = $notification->toSMS($notifiable);

        // ...send $message->getPayload()->to() / ->content() via your own SMS provider.
    }
}
```

```php
//config/otp.php
<?php

return [
    // ...

    'channel' => \App\Channels\CustomChannel::class,
];
```

> If `otp.channel` is left unset, `send()` throws a `Fouladgar\OTP\Exceptions\OTPException` instead of silently doing
> nothing. This requirement doesn't apply if you use [`withoutNotify()`](#without-notify).

> **Note:** The recipient you pass to `send()` is registered as the routing target for *every* channel you configured
> (via Laravel's on-demand notifications), so built-in channels like `mail` work out of the box too — e.g.
> `OTP::channel('mail')->send('info@example.com')` will actually deliver an email.

### SMS Client

> This section only applies if you use the bundled `Fouladgar\OTP\Notifications\Channels\OTPSMSChannel` (see
> [Notification Channel](#notification-channel-required) above). If you use your own notification channel, skip this.

You can use any SMS service for sending the OTP message — it's entirely your choice.

For sending notifications via the bundled channel, first you need to implement the `Fouladgar\OTP\Contracts\SMSClient`
contract. This contract requires you to implement a `sendMessage` method.

This method receives a `Fouladgar\OTP\Notifications\Messages\MessagePayload` object which contains the **recipient**
and **token** message, and should return your SMS service's API result:

```php
<?php

namespace App;

use Fouladgar\OTP\Contracts\SMSClient;
use Fouladgar\OTP\Notifications\Messages\MessagePayload;

class SampleSMSClient implements SMSClient
{
    public function __construct(protected SampleSMSService $SMSService)
    {
    }

    public function sendMessage(MessagePayload $payload): mixed
    {
        return $this->SMSService->send($payload->to(), $payload->content());
    }

    // ...
}
```

> In the example above, `SMSService` can be replaced with your chosen SMS service along with its respective method.

Next, set the client wrapper `SampleSMSClient` class in the config file:

```php
// config/otp.php

<?php

return [

  'sms_client' => \App\SampleSMSClient::class,

  //...
];
```

### Token Storage

The package lets you store the generated one-time password on either a `cache` or `database` driver — the default
is `cache`.

You can change the preferred driver through the config file that we published earlier:

```php
// config/otp.php

<?php

return [
    /**
    |Supported drivers: "cache", "database"
    */
    'token_storage' => 'cache',
];
```

##### Cache

Note that `Laravel OTP` uses your application's already-configured `cache` driver to store the token. If you haven't
configured one yet (or don't plan to), use `database` instead.

##### Database

This means, after migrating, a table will be created for your application to store verification tokens.

> If you're using another name for the `otp_tokens` table, you can customize it in the config file:

```php
// config/otp.php

<?php

return [

    'token_table' => 'otp_token',

    //...
];

```

The package's migrations are loaded automatically, so all that's left is:

```
php artisan migrate
```

### Token Life Time

You can specify an OTP `token_lifetime`, ensuring that once an OTP token is sent, no new token will be generated or
sent for the same recipient until the current one has expired.

```php
// config/otp.php

<?php

return [
    //...

    'token_lifetime' => env('OTP_TOKEN_LIFE_TIME', 5),

    //...
];
```

You can also change the length of the generated token via `token_length` (default `5`):

```php
// config/otp.php

<?php

return [
    //...

    'token_length' => env('OTP_TOKEN_LENGTH', 5),

    //...
];
```

## Basic Usage

Once configured, sending and validating tokens looks like this. The recipient you pass to `send()`/`validate()` can
be a mobile number, an email address, or any other identifier — whatever your configured notification channel(s)
know how to deliver to.

```php
<?php

use Fouladgar\OTP\Facades\OTP;

/*
|--------------------------------------------------------------------------
| Send OTP to a mobile number (e.g. via the bundled "otp_sms" channel).
|--------------------------------------------------------------------------
*/

OTP::send('+98900000000');
// Or
OTP('+98900000000');

/*
|--------------------------------------------------------------------------
| Send OTP to an email address.
|--------------------------------------------------------------------------
| Just point "otp.channel" to "mail" (globally in config/otp.php, or per-call
| as shown below) and pass an email address as the recipient instead of a
| mobile number.
*/

OTP::channel('mail')->send('user@example.com');
// Or
OTP('user@example.com', ['mail']);

/*
|--------------------------------------------------------------------------
| Send OTP via multiple channels at once, for this call only.
|--------------------------------------------------------------------------
| Every channel you list receives the SAME recipient value, so only combine
| channels that can all handle that value's shape — e.g. "otp_sms" and your
| own custom SMS-shaped channel both accept a mobile number here. Don't mix
| in "mail" unless the recipient is actually an email address.
*/

OTP::channel(['otp_sms', \App\Channels\CustomChannel::class])
   ->send('+98900000000');
// Or
OTP('+98900000000', ['otp_sms', \App\Channels\CustomChannel::class]);

/*
|--------------------------------------------------------------------------
|  Validate OTP.
|--------------------------------------------------------------------------
| Works the same regardless of whether the recipient is a mobile number or
| an email address — it just has to match what send() was called with.
*/

OTP::validate('+98900000000', 'token_123');
OTP::validate('user@example.com', 'token_123');
// Or
OTP('+98900000000', 'token_123');

/*
|--------------------------------------------------------------------------
| Get the last generated token right after send() (e.g. for logging/testing).
|--------------------------------------------------------------------------
*/

$otp = OTP();
$otp->send('+98900000000');
$otp->getToken();

/*
|--------------------------------------------------------------------------
| Revoke a pending OTP without validating it.
|--------------------------------------------------------------------------
*/
OTP::revoke('+98900000000');
```

> Both `send()` and `validate()` return a `bool`. On failure (invalid/expired token, or an OTP already pending for
> that recipient), a `Fouladgar\OTP\Exceptions\OTPException` is thrown instead of returning `false`.

You can also inject `Fouladgar\OTP\OTPBroker` directly wherever you need it (e.g. in a controller or service class) —
the facade and the `OTP()` helper are just convenient shortcuts around the same class.

## Purpose

Every token is generated for a `recipient` **and** a `purpose` — a short scope tag so multiple independent OTPs can
co-exist for the same recipient at the same time.

Without it, if a user requested a "login" OTP and then, before validating it, also requested a "password reset" OTP
for the same number, the second `send()` would either collide with the first or you'd need your own bookkeeping to
tell them apart. `purpose()` solves this by scoping the token record — think of it as answering "what is this
specific one-time code actually for?"

```php
use Fouladgar\OTP\Facades\OTP;

OTP::purpose('login_')->send('+98900000000');
OTP::purpose('password_reset_')->send('+98900000000');

// Each purpose is tracked independently — validating the "password_reset_"
// token below has no effect on the still-pending "login_" one.
OTP::purpose('password_reset_')->validate('+98900000000', 'token_123');
```

- **`validate()` and `revoke()` must be called with the same purpose `send()` used** — otherwise they simply won't
  find a matching record (exactly as if the token never existed).
- If you never call `->purpose()`, the default value from the `otp.prefix` config option (`'otp_'` out of the box)
  is used for every call — which is all you need for apps that only ever send one kind of OTP per recipient.
- `purpose` (together with `recipient`) is also what `OTPException::whenOtpAlreadySent()` keys off: two `send()`
  calls for the same recipient with *different* purposes never conflict; the same recipient **and** purpose does.

## Without Notify

If a separate service in your system is responsible for actually delivering the SMS/email (e.g. a dedicated
"notification" microservice), you probably don't want this package attempting delivery itself. Call `withoutNotify()`
before `send()` to only generate and store the token — no channel needs to be configured, and no notification is
dispatched:

```php
use Fouladgar\OTP\Facades\OTP;

OTP::withoutNotify()->send('09389599530');
```

You then pick the token up yourself — either straight from `getToken()` right after `send()`, or (more usefully in a
decoupled/microservice setup) via the `TokenCreated` event, so a listener can hand it off to your own notification
service (see [Events](#events)):

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

`validate()` and `revoke()` behave exactly the same regardless of `withoutNotify()` — it only ever affects `send()`.

## Customization

### Notification SMS and Email Customization

OTP notification prepares a default sms and email format that satisfies most applications. However, you can
customize how the mail/sms message is constructed.

To get started, pass a closure to the `toSMSUsing`/`toMailUsing` method provided by
the `Fouladgar\OTP\Notifications\OTPNotification` notification. The closure receives the `recipient` the token was
generated for as well as the `token` itself. Typically, you should call those methods from the boot method of your
application's `App\Providers\AppServiceProvider` class:

```php
<?php

use Fouladgar\OTP\Notifications\OTPNotification;
use Fouladgar\OTP\Notifications\Messages\OTPMessage;
use Illuminate\Notifications\Messages\MailMessage;

public function boot()
{
    // ...

    // SMS Customization
    OTPNotification::toSMSUsing(fn($recipient, $token) =>(new OTPMessage())
                    ->to($recipient)
                    ->content('Your OTP Token is: '.$token)
                    ->template('OTP_TEMPLATE'));

    //Email Customization
    OTPNotification::toMailUsing(fn ($recipient, $token) =>(new MailMessage)
            ->subject('OTP Request')
            ->line('Your OTP Token is: '.$token));
}
```

## Events

`OTPBroker` dispatches events at each step of the token lifecycle. Listen for them the way you normally would in
Laravel (`Event::listen()`, a listener class, `App\Providers\EventServiceProvider`, etc.).

This is especially useful in a microservice setup: instead of (or in addition to) relying on this package's built-in
delivery, listen for `TokenCreated` and hand the token off to your own notification service (publish to a queue,
call an HTTP API, etc.) — combine with [`withoutNotify()`](#without-notify) if you don't want this package to attempt
delivery at all.

| Event | Fired when | Payload | Cancelable |
|---|---|---|---|
| `Fouladgar\OTP\Events\CreatingToken` | Right before a token is generated | `recipient`, `purpose` | Yes — return `false` from a listener to abort; `send()` returns `false` and nothing is created |
| `Fouladgar\OTP\Events\TokenCreated` | Right after the token is generated and stored | `recipient`, `token`, `purpose` | No |
| `Fouladgar\OTP\Events\TokenCreationFailed` | `send()` aborted because a token is already pending for this recipient/purpose | `recipient`, `purpose` | No |
| `Fouladgar\OTP\Events\SendingNotification` | Right before the built-in notification is dispatched (skipped entirely when using `withoutNotify()`) | `recipient`, `token`, `channels` | Yes — return `false` to skip built-in delivery for this call only |
| `Fouladgar\OTP\Events\NotificationSent` | Right after the built-in notification has been handed off | `recipient`, `token`, `channels` | No |
| `Fouladgar\OTP\Events\TokenValidated` | `validate()` succeeded (before the token is revoked) | `recipient`, `purpose` | No |
| `Fouladgar\OTP\Events\TokenValidationFailed` | `validate()` was called with a missing, invalid, or expired token | `recipient`, `purpose` | No |
| `Fouladgar\OTP\Events\TokenRevoked` | A pending token was actually deleted — via an explicit `revoke()` call, or automatically after a successful `validate()` | `recipient`, `purpose` | No |

> **Note:** `TokenCreationFailed` and `TokenValidationFailed` exist mainly for security monitoring (e.g. detecting
> repeated OTP requests or brute-force guesses). We deliberately did **not** add an event for the "no channel
> configured" error — that's a one-time misconfiguration you catch immediately via the thrown exception, not a
> runtime signal worth monitoring.

## Translates

To publish the translation file you may use this command:

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

```sh
composer test
```

### Faking a Token in Your Application's Tests

When testing a flow in your own application (e.g. an OTP login endpoint), you need a valid token to submit without
actually receiving an SMS/email. Use `OTP::fake()` instead of reaching into the cache/database directly — it stores
a real, valid token using your current configuration (storage driver, `purpose`, etc.), without sending anything:

```php
<?php

use Fouladgar\OTP\Facades\OTP;

// A random token is generated and stored for you — grab it with the return value.
$token = OTP::fake('09389599530');

// Or force a specific value if your test needs to assert on it:
$token = OTP::fake('09389599530', '12345');

// Pass a purpose directly if you're not using the default one — no need for ->purpose() first:
$token = OTP::fake('09389599530', '12345', 'login_');

$this->postJson('/login-otp', [
    'mobile' => '09389599530',
    'token' => $token,
]);
```

> `fake()` skips the "already sent" check and doesn't dispatch any events — it's purely a test-setup helper, not
> part of the `send()`/`validate()` flow.

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

Built with :heart: for you.