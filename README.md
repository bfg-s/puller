# Puller (Long Pull)

[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
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
\Puller::user(Auth::user())
    ->channel('my_test')
    ->stream('Hello world!');
```
Further, in the browser, in the developer console, you will see the reaction.

## Advanced puller
> If you want the main processing and data from receiving data 
> on the side of the task recipient, you must use that advanced `Pullers`, 
> since the `handle` method is always performed on the recipient side.

Generate worker:
```cli
php artisan make:task MyTestPull
```
After that, you will have a class worker:
`app/Pulls/MyTestPull.php`

```php
<?php

namespace App\Pulls;

use Bfg\Puller\Task;

class MyTestPull extends Task
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
\App\Pulls\MyTest::user(\Auth::user())
    ->stream();
```
Further, in the browser, in the developer console, you will see the reaction.

### Another example with the designer
`app/Pulls/SayHelloPull.php`

```php
<?php

namespace App\Pulls;

use Bfg\Puller\Task;

class MyTestPull extends Task
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

### "stream" Dispatch to everyone tabs of selected user
Current authorized user So will always be installed by default, 
it is simply indicated by an example of a user transmission, 
you can pass as a model there and the identifier.

```php
\App\Pulls\MyTest::user(\Auth::user()) // Current auth user set  by default
    ->stream('Administrator');
```
### "flux" Dispatch to everyone online user

```php
\App\Pulls\MyTest::flux('Administrator');
```
### "flow" Dispatch to current tab (if exists)

```php
\App\Pulls\MyTest::flow('Administrator');
\App\Pulls\MyTest::new()->channel('my_test')->flow('Administrator');
```
### "totab" Dispatch to selected tab

```php
\App\Pulls\MyTest::totab($tabid, 'Administrator');
\App\Pulls\MyTest::new()->channel('my_test')->totab($tabid, 'Administrator');
```

## Create class with dot
```cli
php artisan make:task DarkMode_Toggle
```
> Well be generated `dark_mode.toggle` name

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

### Get current process tab (read from header 'Puller-KeepAlive')
```php
\Puller::myTab();
```

### Create new anonymous task
```php
\Puller::new();
```

### Create new anonymous task with channel
```php
\Puller::channel();
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
\App\Pulls\MyTest::modelWatchToStream( 
    \App\Modeld\Message::class,
    $events = [] // 'updated', 'created', 'deleted' by default
);
\App\Pulls\MyTest::modelWatchToFlow(
    \App\Modeld\Message::class,
    $events = [] // 'updated', 'created', 'deleted' by default
);
\App\Pulls\MyTest::modelWatchToFlux(
    \App\Modeld\Message::class,
    $events = [] // 'updated', 'created', 'deleted' by default
);
\App\Pulls\MyTest::modelOwnerWatchToStream(
    \App\Modeld\Message::class,
    $owner_field = "user_id",
    $events = [] // 'updated', 'created', 'deleted' by default
);
\App\Pulls\MyTest::modelOwnerWatchToFlow(
    \App\Modeld\Message::class,
    $owner_field = "user_id",
    $events = [] // 'updated', 'created', 'deleted' by default
);
\App\Pulls\MyTest::modelOwnerWatchToFlux(
    \App\Modeld\Message::class,
    $owner_field = "user_id",
    $events = [] // 'updated', 'created', 'deleted' by default
);
// Or
\App\Pulls\MyTest::modelWatchToFlow([
    \App\Modeld\Message::class,
    \App\Modeld\User::class,
]);
```
> The report will be sent to the user the identifier of which is 
> called in this column that you indicated in the property `$owner_field` 
> (may be an array with a list of several columns).

## Move Zone
The area of liability movement, if the zone will be released inside the zone, 
the zone will fix it and posts the call to the mass control queue, and the general 
call may already delegate the type of shipment. Thus, we have mass control over tasks.
```php
\Puller::moveZone('admin', function () {
    \Puller::channel('test')->detail('hi');
    \Puller::channel('test2')->detail('hi2');
    \Puller::channel('test3')->detail('hi3');
})->flux();
```

## JavaScript
You have a globally registered `Puller` object that is intended for external control.
```javascript
Puller.tab(); // Get current tab id.
Puller.run(); // Run subscription.
Puller.stop(); // Stop subscription.
Puller.restart(); // Reconnect subscription will make stop and launch.
Puller.channel(name, callback); // Add Channel handler (Alpine and Livewire are channels).
Puller.state(name, value); // Set or unset the state for uninterrupted communication.
Puller.emit(channel, name, detail); // Reply imitation to `Puller`.
Puller.dispatch(eventName, detail); // Dispatch a browser event.
Puller.message(eventName, data); // Send a message with the name of events and data for Backend.
```

### Messaging
`Messages` - this is a mechanism for performing tasks in a stream that distributes tasks to the current execution request and on the connections.

In order to use the messaging mechanism you should know the minimum data type specification.

#### What is a message on backend?
Message is a signed request for an event. 
What to transmit the names of the events and 
at the same time not to transmit its full 
range of names, the system is looking for 
nesting in any space that is compiled depending 
on your security guard, the default is `web` 
So your nesting prefix will be the next `WebMessage` 
And all created and declared Events and will cause them 
consistently if there will be several events in one name.

Event search occurs on the following pattern:
> Send name: `my-event` or `my`;
> 
> Called Event: `*`\WebMessage\MyEvent

> Send name: `actions:my-event` or `actions:my`;
> 
> Called Event: `*`\WebMessageActions\MyEvent

> `*` - Maybe any value.

All events `Puller` send from such an event will be inserted in 
response to the request and the task will be distributed to what 
a service station can be processed now and what needs to be 
sent to others. 

All transmitted values in the message will be added as a form to request.

#### View all events that can handle a message:
```cli
php artisan puller:events
```

## Plugins

### Livewire support
https://github.com/bfg-s/puller-livewire

### Alpine support
https://github.com/bfg-s/puller-alpine

## Changelog
Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Security
If you discover any security-related issues, please email xsaven@gmail.com instead of using the issue tracker.

## License
The MIT License (MIT). Please see [License File](/LICENSE.md) for more information.
