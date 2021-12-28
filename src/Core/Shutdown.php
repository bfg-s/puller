<?php

namespace Bfg\Puller\Core;

class Shutdown {

    protected $functions = [];

    protected $enabled = true;

    public function __construct()
    {
        register_shutdown_function([$this, 'onShutdown']);
    }

    public function onShutdown() {
        if (!$this->enabled) {
            return;
        }

        foreach ($this->functions as $fnc) {
            call_user_func($fnc);
        }
    }

    public function keys()
    {
        return array_keys($this->functions);
    }

    public function clear() {
        $this->functions = [];
    }

    public function disable() {
        $this->enabled = false;
    }

    public function setEnabled($value) {
        $this->enabled = (bool)$value;
    }

    public function getEnabled() {
        return $this->enabled;
    }

    public function forgetFunction(string $name)
    {
        unset($this->functions[$name]);
    }

    public function registerFunction(callable $fnc, string $name = null) {
        if ($name) {
            $this->functions[$name] = $fnc;
        } else {
            $this->functions[] = $fnc;
        }
    }
}
