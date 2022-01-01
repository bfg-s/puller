<?php

namespace Bfg\Puller\Facades;

use Illuminate\Support\Facades\Facade;

class PullerFacade extends Facade
{
    /**
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return PullerFacadeInstance::class;
    }
}
