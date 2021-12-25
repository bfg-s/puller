# Puller (Long Pull)

[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Travis](https://img.shields.io/travis/bfg/puller.svg?style=flat-square)]()
[![Total Downloads](https://img.shields.io/packagist/dt/bfg/puller.svg?style=flat-square)](https://packagist.org/packages/bfg/puller)

## Install
`composer require bfg/puller`

## In a nutshell
This package is intended for cases when you need a minimum real time, 
but to raise the WebSocket too expensive or meaningless. 
The essence of the package is the simplest Long Pull, 
which is easy to install and configure. 
It added control over the user's tabs, 
which allows us to control the status of all the user 
tabs and send commands to all the user tabs at all, 
to all browsers. So, we have an excellent opportunity to 
follow the online meter and a real-time list of users.

## Usage
In order to start using, you need to make a couple of simple things:

1. Make sure that in your `public/vendor` folder published `puller/puller.js`. 
He had to appear immediately after installing the package, 
as it broads publications in the `laravel-assets` group. 
If this did not happen and you did not appear there, publish it manually:
```bash
php artisan vendor:publish --tag=puller-assets
```
2. Connect the script in your document:
```html
<script src="{{ asset('vendor/puller/puller.js') }}"></script>
```
3. Prepare your own document event listener:
```javascript
document.addEventListener('my-test', function ({detail}) {
    console.log(detail);
});
```
4. Submit the Puller command:
```php
\Puller::new()
    ->for(Auth::user())
    ->like('my-test')
    ->with('Hello world!')
    ->dispatch();
```
Further, in the browser, in the developer console, you will see the reaction.

## Advanced puller
Generate worker:
```cli
php artisan make:pull MyTestPull
```
After that, you will have a class worker:
`app/Pulls/MyTestPull.php`
```php
<?php

namespace App\Pulls;

use Bfg\Puller\Pull;

class MyTestPull extends Pull
{
    public function handle () {
        //
    }
}
```
What will be described in the Handle method will be performed on the client 
side when the task is performed. And the fact that this method will return, 
will be sent as the details of the event. The event name will be generated 
automatically or you can specify the `protected ?string $name = "my_name";` property.
```php
    public function handle () {
        return "Hello world!";
    }
```
Prepare your own document event listener:
```javascript
document.addEventListener('my-test-pull', function ({detail}) { // 
    console.log(detail);
});
```
> The name of the class-based event will be generated in 
> `Snake-Case`, `MyTestPull` will turn into a `my-test-pull`.

Submit the Puller worker:
```php
\App\Pulls\MyTestPull::for(\Auth::user())
    ->dispatch();
```
Further, in the browser, in the developer console, you will see the reaction.

### Another example with the designer:
`app/Pulls/SayHelloPull.php`
```php
<?php

namespace App\Pulls;

use Bfg\Puller\Pull;

class MyTestPull extends Pull
{
    protected $user_name;

    public function __construct(string $user_name) {
        $this->user_name = $user_name;
    }

    public function handle () {
        return "Hello, " . $this->user_name;
    }
}
```
Submit the Puller worker:
```php
\App\Pulls\MyTestPull::for(\Auth::user())
    ->dispatch('Administrator');
```

## Changelog
Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Credits

- [BFG](https://github.com/bfg-s)
- [All Contributors](https://github.com/bfg-s/puller/contributors)

## Security
If you discover any security-related issues, please email xsaven@gmail.com instead of using the issue tracker.

## License
The MIT License (MIT). Please see [License File](/LICENSE.md) for more information.
