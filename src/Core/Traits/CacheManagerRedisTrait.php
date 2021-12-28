<?php

namespace Bfg\Puller\Core\Traits;

use Illuminate\Support\Facades\Redis as LaravelRedis;

trait CacheManagerRedisTrait
{
    /**
     * @return mixed|\Redis
     */
    public function redis () {
        return LaravelRedis::client();
    }
    /**
     * @return mixed|\Redis
     */
    public function redis_transaction ($closure) {
        return LaravelRedis::transaction($closure);
    }

    public function redis_key(...$params): string
    {
        return  \Cache::getPrefix()
            . "puller_redis:{$this->guard}:" . implode(":", $params);
    }

    public function redis_keys(string $pattern)
    {
        $prefix = LaravelRedis::client()->getOption(\Redis::OPT_PREFIX);
        $result = [];
        foreach ($this->redis()->keys($pattern) as $key) {
            $key = str_replace($prefix, '', $key);
            $result[] = $key;
        }
        return $result;
    }

    public function redis_key_user($user_id): string
    {
        return $this->redis_key("user", (string) $user_id);
    }

    public function redis_key_user_tab($tab): string
    {
        return $this->redis_key("tab", $this->user_id, $tab);
    }

    public function redis_key_user_task(string $task): string
    {
        return $this->redis_key($this->user_id, "task", $this->tab, $task);
    }

    public function redis_key_user_any_task(): string
    {
        return $this->redis_key($this->user_id, "task", '*', '*');
    }










//    protected function redis_users()
//    {
//        return LaravelRedis::mget($this->redis_keys_of_users());
//    }
//
//    protected function redis_user_tabs()
//    {
//        return LaravelRedis::mget($this->redis_keys_of_user_tabs());
//    }
//
//    protected function redis_user_tasks()
//    {
//        return LaravelRedis::mget($this->redis_keys_of_tab_tasks());
//    }
//
//
//
//
//    protected function redis_keys_of_users(): array
//    {
//        return LaravelRedis::keys($this->redis_key_of_users());
//    }
//
//    protected function redis_keys_of_user_tabs(): array
//    {
//        return LaravelRedis::keys("redis:tab:{$this->guard}:{$this->user_id}:*");
//    }
//
//    protected function redis_keys_of_tab_tasks(): array
//    {
//        return LaravelRedis::keys("redis:tab:task:{$this->guard}:{$this->user_id}:{$this->tab}:*");
//    }
//
//
//
//
//
//
//    protected function redis_key_of_users(string $append = "*"): string
//    {
//        return "redis:user:{$this->guard}:{$this->user_id}:{$append}";
//    }
//
//    protected function redis_key_of_user_tab(): string
//    {
//        return "puller:tab:{$this->guard}:{$this->user_id}:{$this->tab}";
//    }
}
