<?php

namespace Bfg\Puller;

use Bfg\Puller\Core\Traits\PullDispatch;

class Pull
{
    use PullDispatch;

    protected ?string $guard = null;

    protected ?string $name = null;

    protected ?int $for_id = null;

    public function handle() {
        //
    }

    public function like(string $name)
    {
        $this->name = $name;

        return $this;
    }

    public function likeLivewire(string $name)
    {
        $this->name = "livewire:" . $name;

        return $this;
    }

    public function getName()
    {
        if ($this->name) {
            return $this->name;
        }
        return str_replace(
            ["\\", ":-", "-pull"],
            [":", ":", ""],
            \Str::snake(class_basename(static::class), '-')
        );
    }

    public function getGuard()
    {
        return $this->guard ?? config('puller.guard');
    }

    public function getForId()
    {
        return $this->for_id;
    }
}
