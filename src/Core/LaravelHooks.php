<?php

namespace Bfg\Puller\Core;

class LaravelHooks
{
    public static function getEvents()
    {
        $events = app('events');
        $property = new \ReflectionProperty($events, "listeners");
        $property->setAccessible(true);
        return array_keys(
            $property->getValue($events)
        );
    }
}
