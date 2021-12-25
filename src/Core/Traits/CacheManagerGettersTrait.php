<?php

namespace Bfg\Puller\Core\Traits;

trait CacheManagerGettersTrait
{
    public function calculatedTabs()
    {
        $list = [];
        $waiting = config('puller.waiting');

        foreach (\Cache::get($this->key_of_tabs(), []) as $key => $item) {
            $item['brake'] = $waiting - ($item['touched']-$item['created']);
            $item['live'] = $item['touched']-$item['connect'];
            $item['overdue'] = $item['touched'] < (time()-2);
            $list[$key] = $item;
        }

        return $list;
    }

    public function getTabs()
    {
        return \Cache::get($this->key_of_tabs(), []);
    }

    public function getTab()
    {
        if ($this->tab) {

            $list = $this->getTabs();

            return $list[$this->tab] ?? null;
        }

        return null;
    }

    public function getUsers()
    {
        return \Cache::get($this->key_of_users(), []);
    }
}
