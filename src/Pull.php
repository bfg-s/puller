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

    protected array $states = [];

    protected $delay = 0;

    public function __construct($handle_data = null)
    {
        $this->handle_data = $handle_data;
    }

    public function getDelay()
    {
        return $this->delay;
    }

    public function delay(int $delaySeconds)
    {
        $this->delay = $delaySeconds;

        return $this;
    }

    public function states(array $states = [])
    {
        $this->states = $states;

        return $this;
    }

    public function access()
    {
        return true;
    }

    public function getStates()
    {
        return $this->states;
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
        return $this->guard;
    }

    public function getForId()
    {
        return $this->for_id;
    }


    public function like(string $name)
    {
        $this->name = $name;

        return $this;
    }

    public function likeLivewire(string $name)
    {
        $this->name = "livewire::" . $name;

        return $this;
    }

    public function likeAlpine(string $name)
    {
        $this->name = "alpine::" . $name;

        return $this;
    }
}
