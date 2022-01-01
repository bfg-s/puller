<?php

namespace Bfg\Puller\Core\Traits;

use Bfg\Puller\Core\Trap;
use Bfg\Puller\Middlewares\PullerMessageMiddleware;

trait CacheManagerGettersTrait
{
    public function getTabs()
    {
        return Trap::hasRedisAndCache(function () {
            $keys = $this->redis_keys($this->redis_key_user_tab('*'));
            $tabs = [];
            foreach ($keys ?: [] as $key) {
                $tabs[] = preg_replace('/.*:([^:]+)$/', '$1', $key);
            }
            return array_combine($tabs, $this->redis()->mGet($keys) ?: []);
        }, function () {
            return \Cache::get($this->key_of_tabs(), []);
        });
    }

    public function getTab()
    {
        return Trap::eq($this->tab, function () {
            return Trap::hasRedisAndCache(function () {
                return ['tasks' => $this->redis()->mGet(
                    $this->redis_keys($this->redis_key_user_task('*'))
                )];
            }, function () {
                $list = $this->getTabs();
                return $list[$this->tab] ?? null;
            });
        });
    }

    public function getUsers()
    {
        return Trap::hasRedisAndCache(function () {
            $keys = $this->redis_keys($this->redis_key_user("*"));
            $ids = [];
            foreach ($keys ?: [] as $key) {
                $ids[] = (int)preg_replace('/.*:([^:]+)$/', '$1', $key);
            }
            return array_combine($ids ?: [], $this->redis()->mGet($keys) ?: []);
        }, function () {
            return \Cache::get($this->key_of_users(), []);
        });
    }
}
