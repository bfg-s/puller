<?php

namespace Bfg\Puller\Core\Traits;

use Bfg\Puller\Middlewares\PullerMessageMiddleware;

trait CacheManagerSettersTrait
{
    public function setTab(string $tab)
    {
        $this->tab = $tab;

        return $this;
    }

    public function setTabTask($tasks, array $tabs = null, array $excludedTabs = null)
    {
        if (is_string($tasks)) {
            $tasks = [$tasks];
        }

        if (is_array($tasks)) {
            if (PullerMessageMiddleware::$isRedis) {
                $oldTab = $this->tab;
                $list = $tabs ?: $this->getTabs();
                foreach ($list as $tab => $time) {
                    foreach ($tasks as $task) {
                        $this->tab = is_numeric($tab) ? $time : $tab;
                        if (!$excludedTabs || !in_array($this->tab, $excludedTabs)) {
                            $this->redis()->set($this->redis_key_user_task(uniqid(time())), $task, PullerMessageMiddleware::$tabLifetime);
                        }
                    }
                }
                $this->tab = $oldTab;
                return true;
            } else {
                $list = $this->getTabs();
                foreach ($list as $tab => $item) {
                    if (!$tabs || in_array($tab, $tabs)) {
                        if (!$excludedTabs || !in_array($this->tab, $excludedTabs)) {
                            $list[$tab]['tasks'] = array_merge(
                                $item['tasks'], $tasks
                            );
                        }
                    }
                }

                \Cache::set($this->key_of_tabs(), $list);
                return true;
            }
        }

        return false;
    }

    public function tabTouch()
    {
        if (PullerMessageMiddleware::$isRedis && $this->tab) {

            $this->redis()->set(
                $this->redis_key_user($this->user_id), time(), PullerMessageMiddleware::$tabLifetime
            );

            $this->redis()->set(
                $this->redis_key_user_tab($this->tab), time(), PullerMessageMiddleware::$tabLifetime
            );

        } else if ($this->tab) {
            $list = $this->getTabs();
            if (isset($list[$this->tab])) {
                $list[$this->tab]['touched'] = time();
                \Cache::set($this->key_of_tabs(), $list);
            }
        }
    }

    public function tabTouchCreated()
    {
        if (!PullerMessageMiddleware::$isRedis && $this->tab) {
            $list = $this->getTabs();
            if (isset($list[$this->tab])) {
                $list[$this->tab]['created'] = time();
                \Cache::set($this->key_of_tabs(), $list);
            }
        }
    }
}
