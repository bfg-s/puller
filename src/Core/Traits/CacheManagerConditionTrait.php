<?php

namespace Bfg\Puller\Core\Traits;

trait CacheManagerConditionTrait
{
    public function isHasTab()
    {
        if ($this->tab) {
            $list = $this->getTabs();
            return array_key_exists($this->tab, $list);
        }
        return false;
    }

    public function isHasUser()
    {
        if ($this->user_id) {
            $list = $this->getUsers();
            return array_key_exists($this->user_id, $list);
        }
        return false;
    }

    public function isTaskExists()
    {
        $tab = $this->getTab();

        return $this->tab && count($tab ? $tab['tasks'] : []);
    }
}
