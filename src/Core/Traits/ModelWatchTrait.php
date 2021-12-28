<?php

namespace Bfg\Puller\Core\Traits;

trait ModelWatchTrait
{
    public static function modelWatch(
        string $modelClass,
        $events = []
    ) {
        foreach (static::getDefaultObserverEvents((array)$events) as $event) {
            $modelClass::$$event(function ($model) use ($event) {
                static::new()->dispatch($model, $event);
            });
        }
    }

    public static function reportToOwner(
        string $modelClass,
        $owner_field = "user_id",
        $events = []
    ) {
        foreach (static::getDefaultObserverEvents((array)$events) as $event) {
            $modelClass::$$event(function ($model) use ($event, $owner_field) {
                foreach ((array)$owner_field as $field) {
                    if ($model->{$field}) {
                        static::new()->for($model->{$field})->dispatch($model, $event);
                    }
                }
            });
        }
    }

    protected static function getDefaultObserverEvents(array $events = [])
    {
        return $events[0] ?? ['updated', 'created', 'deleted'];
    }
}
