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

    public function setTabTask($tasks)
    {
        if (is_string($tasks)) {
            $tasks = [$tasks];
        }

        if (is_array($tasks)) {

            if (PullerMessageMiddleware::$isRedis) {
                $mSet = [];
                $oldTab = $this->tab;
                foreach ($tasks as $k=>$task) {
                    foreach ($this->getTabs() as $tab => $time) {
                        if ($time >= (time()-2)) {
                            $this->tab = $tab;
                            $mSet[$this->redis_key_user_task(uniqid($time.'.'.time().'.'.$k.'.'))] = $task;
                        }
                    }
                }
                $this->tab = $oldTab;
                $this->redis()->mset($mSet);
                //dump($mSet);
            } else {
                $list = $this->getTabs();

                foreach ($list as $tab => $item) {
                    $list[$tab]['tasks'] = array_merge(
                        $item['tasks'], $tasks
                    );
                }

                \Cache::set($this->key_of_tabs(), $list);
            }

            return true;
        }

        return false;
    }

    public function tabTouch()
    {
        if (PullerMessageMiddleware::$isRedis && $this->tab) {

            $this->redis()->set(
                $this->redis_key_user_tab($this->tab), time()
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
