<?php

namespace Bfg\Puller\Core\Traits;

trait ModelWatchTrait
{
    public static function modelWatch(
        string $modelClass,
        $events = [],
        bool $everyone = false
    ) {
        foreach (static::getDefaultObserverEvents((array)$events) as $event) {
            $modelClass::$$event(function ($model) use ($event, $everyone) {
                static::new()->{$everyone ? 'everyone' : 'dispatch'}($model, $event);
            });
        }
    }

    public static function modelWatchForEveryone(
        string $modelClass,
        $events = []
    ) {
        static::modelWatch($modelClass, $events, true);
    }

    public static function reportToOwner(
        string $modelClass,
        $owner_field = "user_id",
        $events = [],
        bool $everyone = false
    ) {
        foreach (static::getDefaultObserverEvents((array)$events) as $event) {
            $modelClass::$$event(function ($model) use ($event, $owner_field, $everyone) {
                foreach ((array)$owner_field as $field) {
                    if ($model->{$field}) {
                        static::new()->for($model->{$field})->{$everyone ? 'everyone' : 'dispatch'}($model, $event);
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
