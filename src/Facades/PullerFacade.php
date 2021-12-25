<?php

namespace Bfg\Puller\Facades;

use Bfg\Puller\Puller;
use Illuminate\Support\Facades\Facade;

class PullerFacade extends Facade
{
    /**
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return Puller::class;
    }
}
