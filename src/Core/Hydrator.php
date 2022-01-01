<?php

namespace Bfg\Puller\Core;

use Bfg\Puller\Pull;

class Hydrator
{
    public Pull $task;
    public ?string $guard = null;
    public ?int $user = null;
    public ?string $tab = null;

    /**
     * @param  Pull  $task
     */
    public function __construct(
        Pull $task
    ) {
        $this->task = $task;
    }

    public static function from(string $object, array $arguments, callable $cb = null)
    {
        $taskObject = new $object(...$arguments);
        if ($taskObject instanceof Pull) {
            $hydrator = new Hydrator($taskObject);
            if ($cb) call_user_func($cb, $hydrator);
            return $hydrator;
        }

        return null;
    }

    public function guard(?string $guard)
    {
        $this->guard = $guard ?: $this->task->getGuard();

        return $this;
    }

    public function user(?int $user = null)
    {
        if (is_int($user)) {
            $this->user = $user;
        } else {
            $getUserId = $this->task->getUser();
            $this->user = is_int($getUserId) ? $getUserId : null;
        }

        return $this;
    }

    public function tab(string $tab = null)
    {
        $this->tab = $tab;

        return $this;
    }

    public function methodsForTask(array $array)
    {
        foreach ($array as $method) {

            if (isset($method['name']) && isset($method['arguments'])) {

                $this->task->{$method['name']}(...$method['arguments']);
            }
        }

        return $this;
    }

    public function manager()
    {
        return \Puller::setGuard($this->guard)
            ->setUserId($this->user)
            ->setTab($this->tab)
            ->manager();
    }

    public function serialize()
    {
        return serialize($this->task);
    }
}
