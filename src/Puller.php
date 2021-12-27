<?php

namespace Bfg\Puller;

use Bfg\Puller\Core\CacheManager;

class Puller
{
    /**
     * Instance of cache manager
     * @var CacheManager|null
     */
    protected ?CacheManager $cache_manager = null;

    /**
     * @param  string|null  $guard
     * @return Core\DispatchManager|Pull
     */
    public function new(string $guard = null)
    {
        return Pull::guard($guard);
    }

    /**
     * @param $user
     * @return Core\DispatchManager|Pull
     */
    public function for($user)
    {
        return Pull::for($user);
    }

    /**
     * Maker of cache
     * @param  string|null  $guard
     * @param  int  $user_id
     * @param  string|null  $tab
     * @return CacheManager|\Illuminate\Contracts\Foundation\Application|mixed|null
     */
    public function newManager(string $guard = null, int $user_id = 0, string $tab = null)
    {
        $guard = !$guard ? config('puller.guard') : $guard;

        $this->cache_manager = app(
            CacheManager::class,
            compact('guard', 'user_id', 'tab')
        );

        return $this->cache_manager;
    }

    public function manager()
    {
        if (!$this->cache_manager) {
            return $this->newManager();
        }

        return $this->cache_manager;
    }

    public function users()
    {
        return $this->manager()->getUsers();
    }

    public function identifications()
    {
        $list = $this->users();
        if (isset($list[0])) {
            unset($list[0]);
        }
        return array_keys($list);
    }

    public function online()
    {
        return count($this->users());
    }
}
