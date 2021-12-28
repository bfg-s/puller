<?php

namespace Bfg\Puller\Controllers;

use Bfg\Puller\Pull;
use Illuminate\Http\Request;

class PullerMessageController
{
    public function __invoke(Request $request)
    {
        while (ob_get_level()){
            ob_get_contents();
            ob_end_clean();
        }

        $seconds = 0;
        $manager = \Puller::manager();

        $manager->removeOverdueTab();
        $manager->tabTouchCreated();

        while (true) {

            if (!$manager->isHasTab()) {
                break;
            }

            $manager->tabTouch();

            if ($manager->isTaskExists()) {
                $objects = $manager->getTab();
                $results = $this->applyTasks($objects['tasks']);
                $manager->clearTab();
                if ($results) {
                    return ['results' => $results];
                }
            }
            else {
                echo str_pad('',1024)."\n";
                flush();
            }

            $manager->removeOverdueTab();

            $seconds++;
            sleep(1);
        }

        echo str_pad('',4096)."\n";
        flush();
    }

    protected function applyTasks(array $tasks)
    {
        $results = [];
        foreach ($tasks as $task) {
            try {
                $taskObject = unserialize($task);
                if ($taskObject instanceof Pull) {
                    $handleResult = $taskObject->handle();
                    if ($taskObject->access()) {
                        $name = $taskObject->getName() ?? 'pull';
                        $results[] = ['name' => $name, 'detail' => $handleResult];
                    }
                }
            } catch (\Throwable $throwable) {
                \Log::error($throwable);
            }
        }
        return $results;
    }
}
