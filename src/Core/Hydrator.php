<?php

namespace Bfg\Puller\Core;

use Bfg\Puller\Pull;

class Hydrator
{
    public ?Pull $task = null;
    public ?string $guard = null;
    public ?int $for_id = null;

    /**
     * @param  Pull  $task
     * @param  string|null  $guard
     * @param  int  $for_id
     */
    public function __construct(
        Pull $task,
        string $guard = null,
        $for_id = 0
    ) {
        $this->task = $task;
        $this->guard = $guard;
        $this->for_id = $for_id;
    }

    public function manager($new_guard = null, $new_for_id = null, $tab = null)
    {
        return \Puller::setGuard($new_guard??$this->guard)
            ->setUserId($new_for_id??$this->for_id)
            ->setTab($tab)
            ->manager();
    }

    public function serialize()
    {
        return serialize($this->task);
    }
}
