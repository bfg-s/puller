<?php

namespace Bfg\Puller\Controllers;

use Bfg\Puller\Middlewares\PullerMessageMiddleware;
use Illuminate\Http\Request;

class PullerMessageController
{
    /**
     * @throws \ReflectionException
     */
    public function __invoke($name, Request $request)
    {
        $guard = PullerMessageMiddleware::$guard;
        $events = app('events');
        $property = new \ReflectionProperty($events, "listeners");
        $property->setAccessible(true);
        $eventList = array_keys(
            $property->getValue($events)
        );
        $eventPattern = "*\\" . \Str::of($name)
            ->prepend($guard, 'Message', \Str::contains($name, ":") ? '-' : ':')
            ->append("Event")
            ->camel()
            ->explode(':')
            ->map(function ($i) { return ucfirst($i); })
            ->join('\\');

        $results = [];

        foreach ($eventList as $eventClass) {
            if (\Str::is($eventPattern, $eventClass)) {
                $results = array_merge($this->callEvent($eventClass, $request));
            }
        }

        return array_filter($results);
    }

    protected function callEvent(string $class, Request $request)
    {
        return event(
            app($class, $request->all())
        );
    }
}
