<?php

namespace Bfg\Puller\Core\Traits;

use Bfg\Puller\Core\DispatchManager;
use Illuminate\Database\Eloquent\Model;

trait PullDispatch
{
    public static function guard(string $guard)
    {
        return (new DispatchManager(
            static::class
        ))->guard($guard);
    }

    /**
     * @param $user
     * @return DispatchManager|static
     */
    public static function for($user)
    {
        if ($user instanceof Model) {
            $user = $user->id;
        }

        return new DispatchManager(
            static::class,
            (is_int($user) ? $user : null)
        );
    }

    public static function dispatch(...$arguments): bool
    {
        return (new DispatchManager(
            static::class
        ))->dispatch(...$arguments);
    }
}
