# Puller (Long Pull)

[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Travis](https://img.shields.io/travis/bfg-/puller.svg?style=flat-square)]()
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

## Redis
Be careful, the package uses the default caching system by default, 
with the driver that is listed there, but if you switch the caching 
driver to `redis`, then you will feel a significant increase in speed 
and get a better and accurate task distribution system and online.
```dotenv
...
CACHE_DRIVER=redis
...
```
> To do this, you should have the extension `Redis` for `php`.

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
document.addEventListener('my_test', function ({detail}) {
    console.log(detail);
});
```
4. Submit the Puller command:
```php
\Puller::new()
    ->for(Auth::user())
    ->like('my_test')
    ->with('Hello world!')
    ->dispatch();
```
Further, in the browser, in the developer console, you will see the reaction.

## Advanced puller
> If you want the main processing and data from receiving data 
> on the side of the task recipient, you must use that advanced `Pullers`, 
> since the `handle` method is always performed on the recipient side.

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
document.addEventListener('my_test_pull', function ({detail}) { // 
    console.log(detail);
});
```
> The name of the class-based event will be generated in 
> `snake_case`, `MyTestPull` will turn into a `my_test_pull`.

Submit the Puller worker:
```php
\App\Pulls\MyTestPull::for(\Auth::user())
    ->dispatch();
```
Further, in the browser, in the developer console, you will see the reaction.

### Another example with the designer
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

### Dispatch to everyone tabs of selected user
```php
\App\Pulls\MyTestPull::for(\Auth::user())
    ->dispatch('Administrator');
```
### Dispatch to everyone online user
```php
\App\Pulls\MyTestPull::everyone('Administrator');
```
### Dispatch to current tab (if exists)
```php
\App\Pulls\MyTestPull::new()->like('my_test')->current('Administrator');
```
### Dispatch to selected tab
```php
\App\Pulls\MyTestPull::new()->like('my_test')->totab($tabid, 'Administrator');
```

### Livewire emit
Anonymous dispatch:
```php
\Puller::for(Auth::user())
    ->likeLivewire('livewire_event_name')
    ->with('Hello world!')
    ->dispatch();
```
Advanced dispatch:
```php
\App\Pulls\MyTestPull::for(\Auth::user())
    ->likeLivewire('livewire_event_name')
    ->dispatch();
```
Create default puller for Livewire:
```cli
php artisan make:pull MyTestPull --livewire
```
In Livewire component make event:
```php
class OrderTracker extends Component
{
    public $showNewOrderNotification = false;
 
    // Special Syntax: ['puller:{event}' => '{method}']
    protected $listeners = ['puller:livewire_event_name' => 'notifyNewOrder'];
 
    public function notifyNewOrder()
    {
        $this->showNewOrderNotification = true;
    }
}
```

### Alpine store method call
Anonymous dispatch:
```php
\Puller::for(Auth::user())
    ->likeAlpine('alpine_store.method_name')
    ->with('Hello world!')
    ->dispatch();

// Or

\Puller::for(Auth::user())
    ->likeAlpine('darkMode.toggle')
    ->dispatch();
```
Advanced dispatch:
```php
\App\Pulls\MyTestPull::for(\Auth::user())
    ->likeAlpine('alpine_store.method_name')
    ->dispatch();
```
Create default puller for Alpine:
```cli
php artisan make:pull DarkMode_Toggle --alpine
```
> Well be generated `dark_mode.toggle` name

Alpine store:
```javascript
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.store('dark_mode', {
            status: true,

            toggle() { // <-- details
                this.status = !this.status;
            }
        })
    })
</script>
```
> Details come to the function, or if this property will be assigned to it.

Alpine blade directive:
```blade
@alpineStore('test', ['state' => true])
@alpineStores([
    'chat' => ['list' => []],
    'online' => ['count' => 0],
])
```
Generated:
```html
<script type='text/javascript'>document.addEventListener('alpine:init', function () {Alpine.store("test", {"state":true});})</script>
<script type='text/javascript'>document.addEventListener('alpine:init', function () {Alpine.store("chat", {"list":[]});Alpine.store("online", {"count":0});})</script>
```

## Puller events

### UserOnlineEvent
An event that triggers if the user appeared online.
```php
Event::listen(\Bfg\Puller\Events\UserOnlineEvent::class, function (UserOnlineEvent $event) {
    info("User $event->user_id online");
});
```

### UserOfflineEvent
The event that triggers in the case when the user is lost.
```php
Event::listen(\Bfg\Puller\Events\UserOfflineEvent::class, function (UserOfflineEvent $event) {
    info("User $event->user_id offline");
});
```

### TestListenNewTab
An event that triggers in the case when the user opens a new tab.
```php
Event::listen(\Bfg\Puller\Events\UserNewTabEvent::class, function (UserNewTabEvent $event) {
    info("User $event->user_id new tab $event->tab");
});
```
> Attention! If you reboot a tab, offline and online events will not work, since in fact you stay online if you need an event for each page load, understand that every time you restart the page it is believed that you create a new tab.

### TestListenNewTab
The event that triggers in the case when the user closes the tab.
```php
Event::listen(\Bfg\Puller\Events\UserCloseTabEvent::class, function (UserCloseTabEvent $event) {
    info("User $event->user_id close tab $event->tab");
});
```

## Puller facade

### Create new pull
```php
\Puller::new();
```

### Number of users online
```php
\Puller::online();
```

### List of users online
```php
\Puller::users();
```

### Is online user
```php
\Puller::isOnlineUser(int $user_id);
```

### List of user identifiers online
```php
\Puller::identifications();
```

### Short Event listener setters
```php
\Puller::onOnline(function (UserOnlineEvent $event) {
    info("User $event->user_id online");
});
\Puller::onOffline(callable);
\Puller::onNewTab(callable);
\Puller::onCloseTab(callable);
```

## Model watching
You can use helpers for listeners of model events.
```php
\App\Pulls\MyTestPull::modelWatch( 
    \App\Modeld\Message::class,
    $events = [] // 'updated', 'created', 'deleted' by default
);
\App\Pulls\MyTestPull::modelWatchForEveryone(
    \App\Modeld\Message::class,
    $events = [] // 'updated', 'created', 'deleted' by default
);
\App\Pulls\MyTestPull::reportToOwner(
    \App\Modeld\Message::class,
    $owner_field = "user_id",
    $events = [] // 'updated', 'created', 'deleted' by default
);
```
> The report will be sent to the user the identifier of which is 
> called in this column that you indicated in the property `$owner_field` 
> (may be an array with a list of several columns).

## JavaScript
You have a globally registered `Puller` object that is intended for external control.
```javascript
Puller.run(); // Run subscription.
Puller.stop(); // Stop subscription.
Puller.restart(); // Переподключить подписку, сделает остановку и запуск
Puller.emit(name, detail); // Эмитация ответа на `Puller`
Puller.emitLivewire(name, detail); // Эмитация ответа на `Puller` для `Livewire`
Puller.emitAlpine(name, detail); // Эмитация ответа на `Puller` для `Alpine`
```

## Changelog
Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Security
If you discover any security-related issues, please email xsaven@gmail.com instead of using the issue tracker.

## License
The MIT License (MIT). Please see [License File](/LICENSE.md) for more information.
