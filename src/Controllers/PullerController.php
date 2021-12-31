<?php

namespace Bfg\Puller\Controllers;

class PullerController
{
    protected $states = [];

    protected $results = [];

    public function hasResponse()
    {
        return $this->states || $this->results;
    }

    public function response()
    {
        $states = $this->states;
        $results = $this->results;

        if ($this->hasResponse()) {
            $this->states = [];
            $this->results = [];
            return ['results' => $results, 'states' => $states];
        }

        return [];
    }
}
