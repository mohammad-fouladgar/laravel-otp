# Laravel OTP (One-Time Password)

In the era of platforms' ease of use, most of the web application providing OTP authentication method for their users' convenience. Laravel OTP package equip your application in simple way using handy api so that you as a developer can easily integrate the One-Time Password validation.

![Lazy Cat](https://i.ibb.co/FHb5rp4/lazy-cat.jpg)

## Basic Usage

```php
<?php

/**
 * Send OTP via SMS.
 */
OTP()->send('+989389599530');
// or
OTP('+989389599530');

/**
 * Send OTP via channels.
 */
OTP()->channel(['otp_sms', 'mail', \App\Channels\CustomSMSChannel::class])
     ->send('+989389599530');
// or
OTP('+989389599530', ['otp_sms', 'mail', \App\Channels\CustomSMSChannel::class]);

/**
 * Send OTP for specific user provider
 */
OTP()->useProvider('admins')
     ->send('+989389599530');

/**
 * Validate OTP
 */
OTP()->validate('+989389599530', 'token_123');
// or
OTP('+989389599530', 'token_123');
// or
OTP()->useProvider('users')
     ->validate('+989389599530', 'token_123');
```

## Installation

Package is available on composer:

```bash
$ composer require fouladgar/laravel-otp
```

## Configuration

As next step, let's publish config file `config/otp.php` by executing:

```bash
$ php artisan vendor:publish --provider="Fouladgar\OTP\ServiceProvider" --tag="config"
```

### Password Storage

Package allows you to store the generated one-time password on either `cache` or `database` driver, default is `cache`.

You can change the preferred driver through config file we published earlier:

```php
// config/otp.php

<?php

return [
    /**
     * Supported drivers: "cache", "database"
     */
    'token_storage' => 'cache',
];
```

##### Cache

Note that `Laravel OTP` packages uses the already configured `cache` driver for storage, if you didn't configured one yet or don't have plan to do it you can use `database` instead.

##### Database

Per a migration, a table named `otp_token` will be created and also a column named `mobile` will be added to the existing `users` (or the [default provider](#user-providers)) table in the configured `database`.

Note: we give you more open hand on customizing the table to be consistent with you database naming conventions so that in `config/otp.php` file you may define:
- `token_table`: this package is going to use this table for its purposes
- `mobile_column`: customize the column name to whatever you prefer e.g. `phone_number`

```php
// config/otp.php

<?php

return [

    'mobile_column' => 'mobile',

    'token_table'   => 'otp_token',

    //...
];

```

Perform database migration:

```bash
$ php artisan migrate
```

There we go! 🎉

> **Note:** When you are using OTP for user login purposes, you shall consider that all columns must be `nullable` except for `mobile` column. Because, once OTP verified, a user record will be created if the user does not already exist.

## User Providers

You might want to use `Laravel OTP` for different type of users, in such case Laravel OTP allows you to define and manage user providers as many as you want.

In order to set up, you should open `config/otp.php` file and define your providers:

```php
// config/otp.php

<?php

return [
    //...

    'default_provider' => 'users',

    'user_providers'  => [
        'users' => [
            'table'      => 'users',
            'model'      => \App\Models\User::class, // if Laravel < 8, change it to \App\User::class
            'repository' => \Fouladgar\OTP\NotifiableRepository::class,
        ],

       // 'admins' => [
       //   'model'      => \App\Models\Admin::class,
       //   'repository' => \Fouladgar\OTP\NotifiableRepository::class,
       // ],
    ],

    //...
];
```

> **Note:** You can also change the default repository and replace your own repository however every repository must implement `Fouladgar\OTP\Contracts\NotifiableRepositoryInterface` interface.

#### Model Preparation

Every model should implement `Fouladgar\OTP\Contracts\OTPNotifiable` and use `Fouladgar\OTP\Concerns\HasOTPNotify` trait:

```php
<?php

namespace App\Models;

use Fouladgar\OTP\Concerns\HasOTPNotify;
use Fouladgar\OTP\Contracts\OTPNotifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements OTPNotifiable
{
    use Notifiable;
    use HasOTPNotify;

    // ...
}
```

### SMS Client

You can use any SMS services for sending password (it's totally up to you).

For sending notifications via
this package, first you need to implement the `Fouladgar\OTP\Contracts\SMSClient` contract. This contract requires you
to implement `sendMessage` method.

This method will return your SMS service API results via a `Fouladgar\OTP\Notifications\Messages\MessagePayload` object
which contains user **mobile** and **token** message:

```php
<?php

namespace App;

use Fouladgar\OTP\Contracts\SMSClient;
use Fouladgar\OTP\Notifications\Messages\MessagePayload;

class SampleSMSClient implements SMSClient
{
    protected $SMSService;

    public function sendMessage(MessagePayload $payload)
    {
        // preparing SMSService ...

        return $this->SMSService
                 ->send($payload->to(), $payload->content());
    }

    // ...
}
```

> In above example, `SMSService` can be replaced with your chosen SMS service along with its respective method.

Next, you should set the client wrapper `SampleSMSClient` class in config file:

```php
// config/otp.php

<?php

return [

  'sms_client' => \App\SampleSMSClient::class,

  //...
];
```

## Practical Example

Here we have prepared a practical example. The presume is that you are going to login/register a customer by sending an OTP:

```php
<?php

namespace App\Http\Controllers;

use App\Models\User;
use Fouladgar\OTP\Exceptions\InvalidOTPTokenException;
use Fouladgar\OTP\OTPBroker as OTPService;
use Illuminate\Http\Request;
use Throwable;

class AuthController
{
    /**
    * @var OTPService
    */
    private $OTPService;

    public function __construct(OTPService $OTPService)
    {
        $this->OTPService = $OTPService;
    }

    public function sendOTP(Request $request)
    {
        try {
            /** @var User $customer */
            $customer = $this->OTPService->send($request->get('mobile'));
        } catch (Throwable $ex) {
          // or prepare and return a view.
           return response()->json(['message'=>'An Occurred unexpected error.'], 500);
        }

        return response()->json(['message'=>'A token sent to:'. $customer->mobile]);
    }

    public function verifyOTPAndLogin(Request $request)
    {
        try {
            /** @var User $customer */
            $customer = $this->OTPService->validate($request->get('mobile'), $request->get('token'));

            // and do login actions...

        } catch (InvalidOTPTokenException $exception){
             return response()->json(['error'=>$exception->getMessage()],$exception->getCode());
        } catch (Throwable $ex) {
            return response()->json(['message'=>'An Occurred unexpected error.'], 500);
        }

         return response()->json(['message'=>'Login has been successfully.']);
    }
}

```

## Customization

### Notification Default Channel Customization

For sending OTP notification there is a default channel. But this package allows you to use your own notification
channel. In order to replace, you should specify channel class here:

```php
//config/otp.php
<?php
return [
    // ...

    'channel' => \Fouladgar\OTP\Notifications\Channels\OTPSMSChannel::class,
];
```

> **Note:** If you change the default sms channel, the `sms_client` will be an optional config. Otherwise, you must define your sms client.

### Notification SMS and Email Customization

OTP notification prepares a default sms and email format that are satisfied for most application. However, you can
customize how the mail/sms message is constructed.

To get started, pass a closure to the `toSMSUsing/toMailUsing` method provided by
the `Fouladgar\OTP\Notifications\OTPNotification` notification. The closure will receive the notifiable model instance
that is receiving the notification as well as the `token` for validating. Typically, you should call the those
methods from the boot method of your application's `App\Providers\AuthServiceProvider` class:

```php
<?php

use Fouladgar\OTP\Notifications\OTPNotification;
use Fouladgar\OTP\Notifications\Messages\OTPMessage;
use Illuminate\Notifications\Messages\MailMessage;

public function boot()
{
    // ...

    // SMS Customization
    OTPNotification::toSMSUsing(function($notifiable, $token) {
        return (new OTPMessage())
                    ->to($notifiable->mobile)
                    ->content('Your OTP Token is: '.$token);
    });

    //Email Customization
    OTPNotification::toMailUsing(function ($notifiable, $token) {
        return (new MailMessage)
            ->subject('OTP Request')
            ->line('Your OTP Token is: '.$token);
    });
}
```

## I18n

To publish translation file you may use this command:

```
php artisan vendor:publish --provider="Fouladgar\OTP\ServiceProvider" --tag="lang"
```

you can customize in provided language file:

```php
// resources/lang/vendor/OTP/en/otp.php

<?php

return [
    'otp_token' => 'Your OTP Token is: :token.',

    'otp_subject' => 'OTP request',
];
```

## Testing

```bash
$ composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email fouladgar.dev@gmail.com instead of using the issue tracker.

## License

Laravel-OTP is released under the MIT License. See the bundled
[LICENSE](https://github.com/mohammad-fouladgar/laravel-mobile-verification/blob/master/LICENSE)
file for details.

Built with :heart: for lazy people.
