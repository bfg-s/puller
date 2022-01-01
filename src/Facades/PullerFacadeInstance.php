<?php

namespace Bfg\Puller\Facades;

use Bfg\Puller\Core\CacheManager;
use Bfg\Puller\Core\Trap;
use Bfg\Puller\Interfaces\PullDefaultChannelInterface;
use Bfg\Puller\Middlewares\PullerMessageMiddleware;
use Bfg\Puller\Pulls\AnonymousPull;

class PullerFacadeInstance
{
    protected $online_users = [];
    protected $channal_interfaces = [
        'default' => PullDefaultChannelInterface::class
    ];

    /**
     * Instance of cache manager
     * @var CacheManager|null
     */
    protected ?CacheManager $cache_manager = null;

    protected $tab = null;
    protected $guard = null;
    protected $user_id = null;
    protected bool $redis_mode = false;

    public function __construct()
    {
        $this->redis_mode = config('cache.default') == 'redis';
    }

    public function registerChannelInterface(string $interface)
    {
        if (defined($interface . "::CHANNEL") && $interface::CHANNEL) {

            $this->channal_interfaces[$interface::CHANNEL] = $interface;
        }

        return $this;
    }

    public function channelInterfaces()
    {
        return $this->channal_interfaces;
    }

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

    public function channel(string $channelName, string $name = null)
    {
        return $this->new()->channel($channelName, $name);
    }

    /**
     * @param $user
     * @return \Bfg\Puller\Core\DispatchManager|AnonymousPull|mixed
     */
    public function user($user)
    {
        return $this->new()->user($user);
    }

    /**
     * @param  string|null  $guard
     * @return \Bfg\Puller\Core\DispatchManager|AnonymousPull
     */
    public function new(string $guard = null)
    {
        $guard = $guard ?: config('puller.guard');
        return AnonymousPull::guard($guard);
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
        return Trap::hasRedisAndCache(function ($user_id) {
            return !!$this->manager()->redis()->exists($this->manager()->redis_key_user($user_id));
        }, function ($user_id) {
            return isset($this->users()[$user_id]);
        }, $user_id);
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

    public function isRedisMode()
    {
        return $this->redis_mode;
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
