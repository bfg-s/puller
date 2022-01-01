<?php

namespace Bfg\Puller\Core;

class Trap
{
    protected static ?bool $redis = null;

    public static function eq($condition, callable $callback, $default = null)
    {
        if ($condition) {
            return call_user_func($callback) ?? $default;
        }
        return $default;
    }

    public static function hasRedisAndCache(callable $redisTrap = null, callable $cacheTrap = null, ...$parameters)
    {
        if (static::isRedis()) {

            return $redisTrap ? call_user_func_array($redisTrap, $parameters) : null;
        }

        return $cacheTrap ? call_user_func_array($cacheTrap, $parameters) : null;
    }

    public static function hasCache(callable $cacheTrap, ...$parameters)
    {
        return static::hasRedisAndCache(null, $cacheTrap, ...$parameters);
    }

    public static function hasRedis(callable $redisTrap, ...$parameters)
    {
        return static::hasRedisAndCache($redisTrap, null, ...$parameters);
    }

    protected static function isRedis()
    {
        if (static::$redis === null) {
            static::$redis = \Puller::isRedisMode();
        }
        return static::$redis;
    }
}
