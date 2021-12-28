<?php

namespace Bfg\Puller\Core\Traits;

use Bfg\Puller\Core\DispatchManager;
use Illuminate\Database\Eloquent\Model;

trait PullDispatchTrait
{
    /**
     * @param  string  $guard
     * @return DispatchManager|static
     */
    public static function guard(string $guard)
    {
        return static::new()->guard($guard);
    }

    /**
     * @param $user
     * @return DispatchManager|static
     */
    public static function for($user)
    {
        return static::new()->for($user);
    }

    /**
     * @param ...$arguments
     * @return bool
     */
    public static function dispatch(...$arguments): bool
    {
        return static::new()->dispatch(...$arguments);
    }

    /**
     * @param ...$arguments
     * @return bool
     */
    public static function everyone(...$arguments): bool
    {
        return static::new()->everyone(...$arguments);
    }

    /**
     * @return DispatchManager|static
     */
    public static function new()
    {
        return new DispatchManager(
            static::class
        );
    }
}
