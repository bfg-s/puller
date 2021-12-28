<?php

namespace Bfg\Puller\Core\Traits;

use Bfg\Puller\Middlewares\PullerMessageMiddleware;

trait CacheManagerConditionTrait
{
    public function isHasTab()
    {
        if ($this->tab) {
            if (PullerMessageMiddleware::$isRedis) {
                return $this->redis()->exists($this->redis_key_user_tab($this->tab));
            }
            $list = $this->getTabs();
            return array_key_exists($this->tab, $list);
        }
        return false;
    }

    public function isHasUser()
    {
        if ($this->user_id) {
            if (PullerMessageMiddleware::$isRedis) {
                return $this->redis()->exists($this->redis_key_user($this->user_id));
            }
            $list = $this->getUsers();
            return array_key_exists($this->user_id, $list);
        }
        return false;
    }

    public function isTaskExists(): bool
    {
        if ($this->tab) {
            if (PullerMessageMiddleware::$isRedis) {
                return !!$this->redis()->keys($this->redis_key_user_task('*'));
            }
            $tab = $this->getTab();
            return !!($tab ? $tab['tasks'] : []);
        }
        return false;
    }
}
