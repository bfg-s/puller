<?php

namespace Bfg\Puller;

use Bfg\Puller\Core\Traits\ModelWatchTrait;
use Bfg\Puller\Core\Traits\PullDispatchTrait;

abstract class Task
{
    use PullDispatchTrait, ModelWatchTrait;

    protected ?string $guard = null;

    protected ?string $name = null;

    protected ?int $user = null;

    protected $handle_data = null;

    protected array $states = [];

    protected $delay = 0;

    public ?string $propagation = null;

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
        if (
            defined(static::class . "::CHANNEL")
            && static::CHANNEL
            && !strpos($this->name, '::')
        ) {

            $this->name = static::CHANNEL . "::" . $this->name;
        }

        if ($this->name) {

            return $this->name;
        }
        return str_replace("._", ".", \Str::snake(str_replace('_', '.', class_basename(static::class))));
    }

    public function getGuard()
    {
        return $this->guard;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function channel(string $channelName, string $name = null)
    {
        $this->name = $name ? "$channelName::$name" : $channelName;

        return $this;
    }
}
