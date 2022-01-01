<?php

namespace Bfg\Puller\Core;

use Bfg\Puller\Task;

/**
 * @mixin DispatchManager
 */
class MoveZone
{
    protected static $queue = [];

    protected static $run = false;

    public function work(callable $callable)
    {
        static::$run = true;
        call_user_func($callable);
        static::$run = false;
        return $this;
    }

    public static function isRun()
    {
        return static::$run;
    }

    public function queues()
    {
        return static::$queue;
    }

    public static function register(DispatchManager $manager)
    {
        if (static::isRun()) {
            $id = spl_object_id($manager);
            static::$queue[$id] = $manager;
            return $id;
        }
        return null;
    }

    public static function has(DispatchManager $manager)
    {
        return isset(static::$queue[$manager->id]);
    }

    public static function forget(DispatchManager $manager)
    {
        if (static::has($manager)) {
            unset(static::$queue[$manager->id]);
        }
    }

    public function __call($name, $arguments)
    {
        static::$run = false;
        foreach (static::$queue as $item) {
            $item->{$name}(...$arguments);
        }

        return $this;
    }
}
