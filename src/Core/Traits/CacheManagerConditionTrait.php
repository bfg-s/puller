<?php

namespace Bfg\Puller\Core\Traits;

use Bfg\Puller\Core\Trap;
use Bfg\Puller\Middlewares\PullerMessageMiddleware;

trait CacheManagerConditionTrait
{
    public function isHasTab()
    {
        return Trap::eq($this->tab, function () {
            return Trap::hasRedisAndCache(function () {
                return $this->redis()->exists($this->redis_key_user_tab($this->tab));
            }, function () {
                $list = $this->getTabs();
                return array_key_exists($this->tab, $list);
            });
        }, false);
    }

    public function isHasUser()
    {
        return Trap::eq($this->user_id, function () {
            return Trap::hasRedisAndCache(function () {
                return $this->redis()->exists($this->redis_key_user($this->user_id));
            }, function () {
                $list = $this->getUsers();
                return array_key_exists($this->user_id, $list);
            });
        }, false);
    }

    public function isTaskExists(): bool
    {
        return Trap::eq($this->tab, function () {
            return Trap::hasRedisAndCache(function () {
                return !!$this->redis()->keys($this->redis_key_user_task('*'));
            }, function () {
                $tab = $this->getTab();
                return !!($tab ? $tab['tasks'] : []);
            });
        }, false);
    }
}
