<?php

namespace Bfg\Puller\Controllers;

abstract class PullerController
{
    protected $states = [];

    protected $results = [];

    static array $queue = [];

    static bool $run = false;

    public static function queueable(callable $cb)
    {
        PullerController::run();
        call_user_func($cb);
        PullerController::shutdown();
    }

    public static function canBeRun()
    {
        return \request()->hasHeader('Puller-Message');
    }

    public static function run()
    {
        PullerController::$run = true;
    }

    public static function shutdown()
    {
        PullerController::$run = false;
    }

    public static function isRun()
    {
        return PullerController::$run;
    }

    public static function addQueue(string $task, $addCondition = true)
    {
        if (PullerController::$run && $addCondition) {
            PullerController::$queue[] = $task;
            //dump(PullerController::$queue);
            return true;
        }
        return false;
    }

    public static function forgetQueue($key, $addCondition = true)
    {
        if (isset(PullerController::$queue[$key]) && $addCondition) {
            unset(PullerController::$queue[$key]);
            return true;
        }
        return false;
    }

    public static function getQueue()
    {
        return PullerController::$queue;
    }

    public static function hasQueue()
    {
        return !!count(PullerController::$queue);
    }

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
