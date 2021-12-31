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
    public static function stream(...$arguments): bool
    {
        return static::new()->stream(...$arguments);
    }

    /**
     * @param ...$arguments
     * @return bool
     */
    public static function flux(...$arguments): bool
    {
        return static::new()->flux(...$arguments);
    }

    /**
     * @param ...$arguments
     * @return bool
     */
    public static function flow(...$arguments): bool
    {
        return static::new()->flow(...$arguments);
    }

    /**
     * @param  null  $tab
     * @param ...$arguments
     * @return bool
     */
    public static function totab($tab = null, ...$arguments): bool
    {
        return static::new()->totab($tab, ...$arguments);
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
