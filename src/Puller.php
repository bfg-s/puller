<?php

namespace Bfg\Puller;

use Bfg\Puller\Core\CacheManager;
use Bfg\Puller\Middlewares\PullerMessageMiddleware;
use Carbon\Carbon;

class Puller
{
    protected $online_users = [];

    /**
     * Instance of cache manager
     * @var CacheManager|null
     */
    protected ?CacheManager $cache_manager = null;

    protected $tab = null;
    protected $guard = null;
    protected $user_id = null;

    public function myTab()
    {
        return request()->header('Puller-KeepAlive');
    }

    public function currentTab()
    {
        return $this->tab ?: $this->myTab();
    }

    public function setTab(string $tab = null)
    {
        if ($tab !== null) {
            $this->tab = $tab;
            $this->cache_manager = null;
        }

        return $this;
    }

    public function currentGuard()
    {
        return $this->guard ?: config('puller.guard');
    }

    public function setGuard(string $guard = null)
    {
        if ($guard !== null) {
            $this->guard = $guard;
            $this->cache_manager = null;
        }

        return $this;
    }

    public function currentUserId()
    {
        return $this->user_id ?: \Auth::guard($this->currentGuard())->id();
    }

    public function setUserId(int $user_id = null)
    {
        if ($user_id !== null) {
            $this->user_id = $user_id;
            $this->cache_manager = null;
        }

        return $this;
    }

    /**
     * @param  string|null  $guard
     * @return Core\DispatchManager|Pull
     */
    public function new(string $guard = null)
    {
        $guard = $guard ?: config('puller.guard');
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

    public function manager()
    {
        if (!$this->cache_manager) {
            $this->cache_manager = app(
                CacheManager::class,
                ['guard' => $this->currentGuard(), 'user_id' => $this->currentUserId(), 'tab' => $this->currentTab()]
            );
        }

        return $this->cache_manager;
    }

    public function users()
    {
        return $this->manager()->getUsers();
    }

    public function isOnlineUser(int $user_id)
    {
        if (PullerMessageMiddleware::$isRedis) {

            return !!$this->manager()->redis()->exists($this->manager()->redis_key_user($user_id));
        }

        return isset($this->users()[$user_id]);
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

    public function onOnline(callable $callable)
    {
        \Event::listen(\Bfg\Puller\Events\UserOnlineEvent::class, $callable);
    }

    public function onOffline(callable $callable)
    {
        \Event::listen(\Bfg\Puller\Events\UserOfflineEvent::class, $callable);
    }

    public function onNewTab(callable $callable)
    {
        \Event::listen(\Bfg\Puller\Events\UserNewTabEvent::class, $callable);
    }

    public function onCloseTab(callable $callable)
    {
        \Event::listen(\Bfg\Puller\Events\UserCloseTabEvent::class, $callable);
    }
}
