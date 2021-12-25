<?php

namespace Bfg\Puller\Core\Traits;

trait CacheManagerSettersTrait
{
    public function setTabTask($tasks)
    {
        if (is_string($tasks)) {
            $tasks = [$tasks];
        }

        if (is_array($tasks)) {

            $list = $this->getTabs();

            foreach ($list as $tab => $item) {
                $list[$tab]['tasks'] = array_merge(
                    $item['tasks'], $tasks
                );
            }

            \Cache::set($this->key_of_tabs(), $list);

            return true;
        }

        return false;
    }

    public function tabTouch()
    {
        if ($this->tab) {
            $list = $this->getTabs();
            if (isset($list[$this->tab])) {
                $list[$this->tab]['touched'] = time();
                \Cache::set($this->key_of_tabs(), $list);
            }
        }
    }

    public function tabTouchCreated()
    {
        if ($this->tab) {
            $list = $this->getTabs();
            if (isset($list[$this->tab])) {
                $list[$this->tab]['created'] = time();
                \Cache::set($this->key_of_tabs(), $list);
            }
        }
    }

    public function hasAccessToTab(string $tab)
    {
        return $this->lockTab($tab)->get();
    }
    public function lockTab(string $tab)
    {
        return \Cache::lock("puller:tabs:{$this->guard}:{$tab}:lock", 5);
    }
}
