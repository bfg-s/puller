<?php

namespace Bfg\Puller\Core\Traits;

use Bfg\Puller\Core\Trap;
use Bfg\Puller\Middlewares\PullerMessageMiddleware;

trait CacheManagerCleanerTrait
{
    public function clearTabs()
    {
        Trap::hasCache(function () {
            \Cache::set($this->key_of_tabs(), []);
        });
    }

    public function clearTab()
    {
        Trap::eq($this->tab && $this->isHasTab(), function () {
            Trap::hasRedisAndCache(function () {
                $this->redis()->del(
                    $this->redis_keys($this->redis_key_user_task('*'))
                );
            }, function () {
                $list = $this->getTabs();
                $list[$this->tab] = [
                    'tasks' => [],
                    'created' => $list[$this->tab]['created'],
                    'connect' => time(),
                    'touched' => time()
                ];
                \Cache::set($this->key_of_tabs(), $list);
            });
        });

        return $this;
    }

    public function removeTab()
    {
        Trap::hasRedisAndCache(function () {
            $this->redis()->del(
                $this->redis_keys($this->redis_key_user_task('*'))
            );
            $this->redis()->del(
                $this->redis_keys($this->redis_key_user_tab($this->tab))
            );
        }, function () {
            $this->removeOverdueTab();
            $list = $this->getTabs();

            if ($this->tab && $this->isHasTab()) {
                unset($list[$this->tab]);
                \Cache::set($this->key_of_tabs(), $list);
            }

            $this->user_off = !count($list);
        });

        return $this;
    }

    public function removeOverdueTab()
    {
        Trap::hasCache(function () {
            $list = [];
            foreach ($this->getTabs() as $tab => $item) {
                if ($item['touched'] > (time()-PullerMessageMiddleware::$tabLifetime)) {
                    $list[$tab] = $item;
                }
            }
            \Cache::set($this->key_of_tabs(), $list);
        });

        return $this;
    }

    public function removeUser()
    {
        Trap::hasRedisAndCache(function () {
            $this->redis()->del(
                $this->redis_keys($this->redis_key_user($this->user_id))
            );
        }, function () {
            if ($this->user_off) {
                $list = $this->getUsers();
                unset($list[$this->user_id]);
                \Cache::set($this->key_of_users(), $list);
            }
        });

        return $this;
    }
}
