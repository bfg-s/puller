<?php

namespace Bfg\Puller\Core\Traits;

trait CacheManagerCheckTrait
{
    public function checkTab()
    {
        if (!$this->isHasTab()) {

            $list = $this->getTabs();

            $list[$this->tab] = [
                'tasks' => [],
                'created' => time(),
                'connect' => time(),
                'touched' => time(),
            ];

            \Cache::set($this->key_of_tabs(), $list);
        }

        return $this;
    }

    public function checkUser()
    {
        $list = $this->getUsers();

        if (!isset($list[$this->user_id])) {
            $list[$this->user_id] = [
                'id' => $this->user_id,
                'created' => time(),
                'touched' => time(),
            ];
        } else {
            $list[$this->user_id]['touched'] = time();
        }

        \Cache::set($this->key_of_users(), $list);

        return $this;
    }
}
