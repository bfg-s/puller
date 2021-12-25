<?php

namespace Bfg\Puller\Core\Traits;

trait CacheManagerCleanerTrait
{
    public function clearTabs()
    {
        \Cache::set($this->key_of_tabs(), []);
    }

    public function clearTab()
    {
        if ($this->tab && $this->isHasTab()) {
            $list = $this->getTabs();
            $list[$this->tab] = [
                'tasks' => [],
                'created' => $list[$this->tab]['created'],
                'connect' => time(),
                'touched' => time()
            ];
            \Cache::set($this->key_of_tabs(), $list);
        }

        return $this;
    }

    public function removeTab()
    {
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
        $list = [];
        foreach ($this->getTabs() as $tab => $item) {
            if ($item['touched'] > (time()-2)) {
                $list[$tab] = $item;
            }
        }
        \Cache::set($this->key_of_tabs(), $list);

        return $this;
    }

    public function removeUser()
    {
        if ($this->user_off) {
            $list = $this->getUsers();
            unset($list[$this->user_id]);
            \Cache::set($this->key_of_users(), $list);
        }

        return $this;
    }
}
