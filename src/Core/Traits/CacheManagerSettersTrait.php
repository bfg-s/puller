<?php

namespace Bfg\Puller\Core\Traits;

use Bfg\Puller\Core\Trap;
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

        return Trap::eq(is_array($tasks), function () use ($tasks, $tabs, $excludedTabs) {
            return Trap::hasRedisAndCache(function ($tasks, $tabs, $excludedTabs) {
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
            }, function ($tasks, $tabs, $excludedTabs) {
                $list = $this->getTabs();
                foreach ($list as $tab => $item) {
                    if (!$tabs || in_array($tab, $tabs)) {
                        if (!$excludedTabs || !in_array($tab, $excludedTabs)) {
                            $list[$tab]['tasks'] = array_merge(
                                $item['tasks'], $tasks
                            );
                        }
                    }
                }
                \Cache::set($this->key_of_tabs(), $list);
                return true;
            }, $tasks, $tabs, $excludedTabs);
        }, false);
    }

    public function tabTouch()
    {
        Trap::eq($this->tab, function () {
            Trap::hasRedisAndCache(function () {
                $this->redis()->set(
                    $this->redis_key_user($this->user_id), time(), PullerMessageMiddleware::$tabLifetime
                );

                $this->redis()->set(
                    $this->redis_key_user_tab($this->tab), time(), PullerMessageMiddleware::$tabLifetime
                );
            }, function () {
                $list = $this->getTabs();
                if (isset($list[$this->tab])) {
                    $list[$this->tab]['touched'] = time();
                    \Cache::set($this->key_of_tabs(), $list);
                }
            });
        });
    }

    public function tabTouchCreated()
    {
        Trap::eq($this->tab, function () {
            Trap::hasCache(function () {
                $list = $this->getTabs();
                if (isset($list[$this->tab])) {
                    if (is_array($list[$this->tab])) $list[$this->tab]['created'] = time();
                    \Cache::set($this->key_of_tabs(), $list);
                }
            });
        });
    }
}
