<?php

namespace Bfg\Puller\Traits;

use Bfg\Puller\Core\DispatchManager;

trait Pullable
{
    public function pull(
        string $class
    ) {
        return (new DispatchManager($class))->for($this);
    }
}
