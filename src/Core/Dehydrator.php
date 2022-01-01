<?php

namespace Bfg\Puller\Core;

use Bfg\Puller\Task;

class Dehydrator
{
    public ?Task $task = null;
    public ?string $name = null;
    public $detail = null;
    public int $delay = 0;
    public array $states = [];

    /**
     * @param  string  $task
     */
    public function __construct(
        string $task
    ) {
        $this->task = $this->unserialize($task);
        $this->name = $this->task->getName();
        $this->delay = $this->task->getDelay();
        $this->states = $this->task->getStates();
        $this->detail = $this->handle();
    }

    public static function collection(array $collection, callable $cb, callable $each = null)
    {
        foreach ($collection as $key => $item) {
            $dehydrator = new static($item);
            if ($dehydrator->validated()) {
                call_user_func($cb, $dehydrator, $key);
            }
            if ($each) {
                call_user_func($each, $key);
            }
        }
    }

    public function validated()
    {
        return $this->task && $this->name;
    }

    public function response()
    {
        return ['name' => $this->name, 'detail' => $this->detail];
    }

    protected function handle()
    {
        return $this->task && $this->name ? app()->call([$this->task, 'handle']) : null;
    }

    protected function unserialize($task)
    {
        $result = unserialize($task);

        if (
            $result instanceof Task
            && $result->access()
        ) {
            return $result;
        }

        return null;
    }
}
