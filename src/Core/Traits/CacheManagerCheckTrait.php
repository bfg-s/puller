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
        if (!$this->isHasUser()) {

            $list = $this->getUsers();

            $list[$this->user_id] = $this->user_id;

            \Cache::set($this->key_of_users(), $list);
        }

        return $this;
    }
}
