<?php

namespace Bfg\Puller\Controllers;

use Bfg\Puller\Core\Dehydrator;
use Bfg\Puller\Pull;
use Illuminate\Http\Request;

class PullerKeepAliveController extends PullerController
{
    protected $seconds = 0;

    protected $delays = [];

    public function __invoke(Request $request)
    {
        while (ob_get_level()){
            ob_get_contents();
            ob_end_clean();
        }

        PullerMessageController::$run = true;

        $manager = \Puller::manager();

        $manager->tabTouchCreated();
        $manager->tabTouch();

        $manager->removeOverdueTab();

        while (true) {

            $manager->tabTouch();

            if (!$manager->isHasTab()) {
                break;
            }

            if ($manager->isTaskExists()) {
                $objects = $manager->getTab();
                //$results = $this->applyTasks($objects['tasks']);
                $this->applyTasks($objects['tasks']);
                $manager->clearTab();
            }

            if (PullerMessageController::$queue) {

                $this->applyTasks(PullerMessageController::$queue, true);
            }

            if (isset($this->delays[$this->seconds])) {

                $this->results = array_merge($this->results, $this->delays[$this->seconds]);
                unset($this->delays[$this->seconds]);
            }

            if ($this->hasResponse()) {
                return $this->response();
            } else {
                echo str_pad('',512)."\n";
                flush();
            }

            $manager->removeOverdueTab();

            $this->seconds++;
            sleep(1);
        }

        echo str_pad('',512)."\n";
        flush();
    }

    protected function applyTasks(array $tasks, bool $queue = false)
    {
        Dehydrator::collection($tasks, function (Dehydrator $dehydrator, $key) use ($queue) {
            if ($queue) {
                unset(PullerMessageController::$queue[$key]);
            }
            $this->states = array_merge($this->states, $dehydrator->states);
            if ($dehydrator->delay) {
                $this->delays[$this->seconds+$dehydrator->delay][] = $dehydrator->response();
            } else {
                $this->results[] = $dehydrator->response();
            }
        });

        if ($queue && count(PullerMessageController::$queue)) {

            $this->applyTasks(PullerMessageController::$queue, true);
        }

//        return static::parseTasks($tasks, function ($name, $result, Pull $pull) {
//            $delay = (int)$pull->getDelay();
//            $toTask = ['name' => $name, 'detail' => $result];
//            $this->states = array_merge($this->states, $pull->getStates());
//            if ($delay) {
//                $this->delays[$this->seconds+$delay][] = $toTask;
//                return null;
//            }
//            return $toTask;
//        });
    }

    protected function parseTasks(array $tasks, callable $cb)
    {
        $results = [];
        foreach ($tasks as $task) {
            try {
                $taskObject = unserialize($task);
                if ($taskObject instanceof Pull) {
                    $handleResult = app()->call([$taskObject, 'handle']);
                    if ($taskObject->access()) {
                        $name = $taskObject->getName() ?? 'pull';
                        $result = $cb($name, $handleResult, $taskObject);
                        if ($result) {
                            $results[] = $result;
                        }
                    }
                }
            } catch (\Throwable $throwable) {
                \Log::error($throwable);
            }
        }
        return $results;
    }
}
