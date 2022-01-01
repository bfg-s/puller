<?php

namespace Bfg\Puller\Core\Traits;

trait ModelWatchTrait
{

    public static function modelWatchToStream(
        $modelClasses,
        $events = []
    ) {
        static::modelWatch($modelClasses, "stream", $events);
    }

    public static function modelWatchToFlow(
        $modelClasses,
        $events = []
    ) {
        static::modelWatch($modelClasses, "flow", $events);
    }

    public static function modelWatchToFlux(
        $modelClasses,
        $events = []
    ) {
        static::modelWatch($modelClasses, "flux", $events);
    }

    public static function modelOwnerWatchToStream(
        $modelClasses,
        $owner_field = "user_id",
        $events = []
    ) {
        static::modelOwnerWatch($modelClasses, "stream", $owner_field, $events);
    }

    public static function modelOwnerWatchToFlow(
        $modelClasses,
        $owner_field = "user_id",
        $events = []
    ) {
        static::modelOwnerWatch($modelClasses, "flow", $owner_field, $events);
    }

    public static function modelOwnerWatchToFlux(
        $modelClasses,
        $owner_field = "user_id",
        $events = []
    ) {
        static::modelOwnerWatch($modelClasses, "flux", $owner_field, $events);
    }


    public static function modelWatch(
        $modelClasses,
        string $method,
        $events = []
    ) {
        foreach (static::getDefaultObserverEvents((array)$events) as $event) {
            foreach ((array)$modelClasses as $modelClass) {
                call_user_func([$modelClass, $event], function ($model) use ($event, $method) {
                    static::new()->{$method}($model, $event);
                });
            }
        }
    }

    public static function modelOwnerWatch(
        $modelClasses,
        string $method,
        $owner_field = "user_id",
        $events = []
    ) {
        foreach (static::getDefaultObserverEvents((array)$events) as $event) {
            foreach ((array)$modelClasses as $modelClass) {
                call_user_func([$modelClass, $event], function ($model) use ($event, $owner_field, $method) {
                    foreach ((array)$owner_field as $field) {
                        if ($model->{$field}) {
                            static::new()->user($model->{$field})->{$method}($model, $event);
                        }
                    }
                });
            }
        }
    }

    protected static function getDefaultObserverEvents(array $events = [])
    {
        return $events[0] ?? ['updated', 'created', 'deleted'];
    }
}
