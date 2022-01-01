<?php

namespace Bfg\Puller\Core\Traits;

use Bfg\Puller\Core\Trap;
use Bfg\Puller\Middlewares\PullerMessageMiddleware;

trait CacheManagerCheckTrait
{
    public function checkTab()
    {
        Trap::hasRedisAndCache(function () {
            $this->redis()->set(
                $this->redis_key_user_tab($this->tab), time()+PullerMessageMiddleware::$tabLifetime,
                PullerMessageMiddleware::$tabLifetime
            );
        }, function () {
            $list = $this->getTabs();

            $list[$this->tab] = [
                'tasks' => [],
                'created' => time(),
                'connect' => time(),
                'touched' => time(),
            ];

            \Cache::set($this->key_of_tabs(), $list);
        });

        return $this;
    }

    public function checkUser()
    {
        Trap::hasRedisAndCache(function () {
            $this->redis()->set(
                $this->redis_key_user($this->user_id), time(), PullerMessageMiddleware::$tabLifetime
            );
        }, function () {
            $list = $this->getUsers();

            if (!isset($list[$this->user_id]) || !is_array($list[$this->user_id])) {
                $list[$this->user_id] = [
                    'id' => $this->user_id,
                    'created' => time(),
                    'touched' => time(),
                ];
            } else {
                $list[$this->user_id]['touched'] = time();
            }

            \Cache::set($this->key_of_users(), $list);
        });

        return $this;
    }
}
