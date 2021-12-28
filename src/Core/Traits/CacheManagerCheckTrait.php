<?php

namespace Bfg\Puller\Core\Traits;

use Bfg\Puller\Middlewares\PullerMessageMiddleware;

trait CacheManagerCheckTrait
{
    public function checkTab()
    {
        if (!$this->isHasTab()) {

            if (PullerMessageMiddleware::$isRedis) {

                $this->redis()->set(
                    $this->redis_key_user_tab($this->tab), time()
                );

            } else {

                $list = $this->getTabs();

                $list[$this->tab] = [
                    'tasks' => [],
                    'created' => time(),
                    'connect' => time(),
                    'touched' => time(),
                ];

                \Cache::set($this->key_of_tabs(), $list);
            }
        }

        return $this;
    }

    public function checkUser()
    {
        if (PullerMessageMiddleware::$isRedis) {

            $this->redis()->set(
                $this->redis_key_user($this->user_id), time()
            );

        } else {

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
        }

        return $this;
    }
}
