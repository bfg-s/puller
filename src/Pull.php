<?php

namespace Bfg\Puller;

use Bfg\Puller\Core\Traits\ModelWatchTrait;
use Bfg\Puller\Core\Traits\PullDispatchTrait;
use Bfg\Puller\Interfaces\PullLikeAlpineInterface;
use Bfg\Puller\Interfaces\PullLikeLivewireInterface;

class Pull
{
    use PullDispatchTrait, ModelWatchTrait;

    protected ?string $guard = null;

    protected ?string $name = null;

    protected ?int $for_id = null;

    protected $handle_data = null;

    public function access()
    {
        return true;
    }

    public function handle() {

        return $this->handle_data;
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
        return $this->for_id !== null ? $this->for_id : \Auth::id();
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
        $this->handle_data = $handle;

        return $this;
    }
}
