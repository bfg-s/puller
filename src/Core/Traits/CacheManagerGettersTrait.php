<?php

namespace Bfg\Puller\Core\Traits;

use Bfg\Puller\Middlewares\PullerMessageMiddleware;

trait CacheManagerGettersTrait
{
//    public function calculatedTabs()
//    {
//        $list = [];
//        $waiting = config('puller.waiting');
//
//        foreach (\Cache::get($this->key_of_tabs(), []) as $key => $item) {
//            $item['brake'] = $waiting - ($item['touched']-$item['created']);
//            $item['live'] = $item['touched']-$item['connect'];
//            $item['overdue'] = $item['touched'] < (time()-2);
//            $list[$key] = $item;
//        }
//
//        return $list;
//    }

    public function getTabs()
    {
        if (PullerMessageMiddleware::$isRedis) {

            $keys = $this->redis_keys($this->redis_key_user_tab('*'));

            $tabs = [];

            foreach ($keys ?: [] as $key) {
                $tabs[] = preg_replace('/.*:([^:]+)$/', '$1', $key);
            }

            return array_combine($tabs, $this->redis()->mGet(
                $keys
            ) ?: []);
        }
        return \Cache::get($this->key_of_tabs(), []);
    }

    public function getTab()
    {
        if ($this->tab) {

            if (PullerMessageMiddleware::$isRedis) {

                return [
                    'tasks' => $this->redis()->mGet(
                        $this->redis_keys($this->redis_key_user_task('*'))
                    )
                ];
            }

            $list = $this->getTabs();

            return $list[$this->tab] ?? null;
        }

        return null;
    }

    public function getUsers()
    {
        if (PullerMessageMiddleware::$isRedis) {

            $keys = $this->redis_keys($this->redis_key_user("*"));

            $ids = [];

            foreach ($keys ?: [] as $key) {
                $ids[] = (int)preg_replace('/.*:([^:]+)$/', '$1', $key);
            }

            return array_combine($ids ?: [], $this->redis()->mGet(
                $keys
            ) ?: []);
        }
        return \Cache::get($this->key_of_users(), []);
    }
}
