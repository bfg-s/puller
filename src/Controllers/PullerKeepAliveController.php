<?php

namespace Bfg\Puller\Controllers;

use Bfg\Puller\Core\Dehydrator;
use Bfg\Puller\Task;
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
                $this->applyTasks($objects['tasks']);
                $manager->clearTab();
            }

            if (isset($this->delays[$this->seconds])) {

                $this->results = array_merge($this->results, $this->delays[$this->seconds]);
                unset($this->delays[$this->seconds]);
            }

            $this->applyTasks(static::getQueue(), true);

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
        Dehydrator::collection($tasks, function (Dehydrator $dehydrator) {
            $this->states = array_merge($this->states, $dehydrator->states);
            if ($dehydrator->delay) {
                $this->delays[$this->seconds+$dehydrator->delay][] = $dehydrator->response();
            } else {
                $this->results[] = $dehydrator->response();
            }
        }, function ($key) use ($queue) {
            static::forgetQueue($key, $queue);
        });
        if (static::hasQueue()) {
            $this->applyTasks(static::getQueue(), true);
        }
    }
}
