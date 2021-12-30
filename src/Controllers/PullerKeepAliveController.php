<?php

namespace Bfg\Puller\Controllers;

use Bfg\Puller\Pull;
use Illuminate\Http\Request;

class PullerKeepAliveController
{
    protected $seconds = 0;

    protected $delays = [];

    protected $states = [];

    public function __invoke(Request $request)
    {
        while (ob_get_level()){
            ob_get_contents();
            ob_end_clean();
        }

        $manager = \Puller::manager();

        $manager->tabTouchCreated();
        $manager->tabTouch();

        $manager->removeOverdueTab();

        while (true) {

            $manager->tabTouch();

            if (!$manager->isHasTab()) {
                break;
            }

            $results = [];

            if ($manager->isTaskExists()) {
                $objects = $manager->getTab();
                $results = $this->applyTasks($objects['tasks']);
                $manager->clearTab();
            }

            if (isset($this->delays[$this->seconds])) {

                $results = array_merge($results, $this->delays[$this->seconds]);
                unset($this->delays[$this->seconds]);
            }

            $states = $this->states;

            if ($results || $states) {
                $this->states = [];
                return ['results' => $results, 'states' => $states];
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

    protected function applyTasks(array $tasks)
    {
        $results = [];
        foreach ($tasks as $task) {
            try {
                $taskObject = unserialize($task);
                if ($taskObject instanceof Pull) {
                    $handleResult = app()->call([$taskObject, 'handle']);
                    if ($taskObject->access()) {
                        $name = $taskObject->getName() ?? 'pull';
                        $delay = (int)$taskObject->getDelay();
                        $result = ['name' => $name, 'detail' => $handleResult];
                        if ($delay) {
                            $this->delays[$this->seconds+$delay][] = $result;
                        } else {
                            $results[] = $result;
                        }
                        $this->states = array_merge($this->states, $taskObject->getQuery());
                    }
                }
            } catch (\Throwable $throwable) {
                \Log::error($throwable);
            }
        }
        return $results;
    }
}
