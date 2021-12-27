<?php

namespace Bfg\Puller;

use Bfg\Puller\Core\Traits\PullDispatch;
use Bfg\Puller\Interfaces\PullLikeAlpineInterface;
use Bfg\Puller\Interfaces\PullLikeLivewireInterface;

class Pull
{
    use PullDispatch;

    protected ?string $guard = null;

    protected ?string $name = null;

    protected ?int $for_id = null;

    protected $default_handle_data = null;


    public function handle() {

        return $this->default_handle_data;
    }

    public function getName()
    {
        if ($this->name) {
            if ($this instanceof PullLikeLivewireInterface) {
                $this->likeLivewire($this->name);
            } else if ($this instanceof PullLikeAlpineInterface) {
                $this->likeAlpine($this->name);
            }
            return $this->name;
        }
        return str_replace("._", ".", \Str::snake(str_replace('_', '.', class_basename(static::class))));
    }

    public function getGuard()
    {
        return $this->guard ?? config('puller.guard');
    }

    public function getForId()
    {
        return $this->for_id === null ? $this->for_id : \Auth::id();
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

    public function likeAlpine(string $name)
    {
        $this->name = "alpine:" . $name;

        return $this;
    }

    public function with($handle)
    {
        $this->default_handle_data = $handle;

        return $this;
    }
}
