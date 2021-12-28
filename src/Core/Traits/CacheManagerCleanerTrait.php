<?php

namespace Bfg\Puller\Core\Traits;

use Bfg\Puller\Middlewares\PullerMessageMiddleware;

trait CacheManagerCleanerTrait
{
    public function clearTabs()
    {
        if (PullerMessageMiddleware::$isRedis) {

            $this->redis()->del(
                $this->redis_keys($this->redis_key_user_tab('*'))
            );
            $this->redis()->del(
                $this->redis_keys($this->redis_key_user_any_task())
            );

        } else {
            \Cache::set($this->key_of_tabs(), []);
        }
    }

    public function clearTab()
    {
        if ($this->tab && $this->isHasTab()) {
            if (PullerMessageMiddleware::$isRedis) {

                $this->redis()->del(
                    $this->redis_keys($this->redis_key_user_task('*'))
                );

            } else {
                $list = $this->getTabs();
                $list[$this->tab] = [
                    'tasks' => [],
                    'created' => $list[$this->tab]['created'],
                    'connect' => time(),
                    'touched' => time()
                ];
                \Cache::set($this->key_of_tabs(), $list);
            }
        }

        return $this;
    }

    public function removeTab()
    {
        if (PullerMessageMiddleware::$isRedis) {

            if ($this->tab && $this->isHasTab()) {

                $this->redis()->del($this->redis_key_user_tab($this->tab));
                $this->redis()->del(
                    $this->redis_keys($this->redis_key_user_task("*"))
                );
            }

            return $this;
        }

        $this->removeOverdueTab();
        $list = $this->getTabs();

        if ($this->tab && $this->isHasTab()) {
            unset($list[$this->tab]);
            \Cache::set($this->key_of_tabs(), $list);
        }

        $this->user_off = !count($list);

        return $this;
    }

    public function removeOverdueTab()
    {
        if (!PullerMessageMiddleware::$isRedis) {

            $list = [];
            foreach ($this->getTabs() as $tab => $item) {
                if ($item['touched'] > (time()-2)) {
                    $list[$tab] = $item;
                }
            }
            \Cache::set($this->key_of_tabs(), $list);
        }

        return $this;
    }

    public function removeUser()
    {
        if (PullerMessageMiddleware::$isRedis) {

            if (!count($this->redis_keys($this->redis_key_user_tab('*')))) {

                $this->redis()->del($this->redis_key_user($this->user_id));
            }
        } else {

            if ($this->user_off) {
                $list = $this->getUsers();
                unset($list[$this->user_id]);
                \Cache::set($this->key_of_users(), $list);
            }
        }

        return $this;
    }
}
