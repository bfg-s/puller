<?php

namespace Bfg\Puller\Traits;

use Bfg\Puller\Core\DispatchManager;

trait Pullable
{
    public function task(
        string $class
    ) {
        return (new DispatchManager($class))->user($this);
    }
}
