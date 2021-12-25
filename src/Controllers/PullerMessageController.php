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

        //dd(1);

        $seconds = 0;
        $waiting = config('puller.waiting', 30);
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
                $results = [];
                foreach ($objects['tasks'] as $task) {
                    try {
                        $taskObject = unserialize($task);
                        if ($taskObject instanceof Pull) {
                            $handleResult = $taskObject->handle();
                            $name = $taskObject->getName() ?? 'pull';
                            $results[] = ['name' => $name, 'detail' => $handleResult];
                        }
                    } catch (\Throwable $throwable) {
                        \Log::error($throwable);
                    }
                }
                $manager->clearTab();
                return ['results' => $results];
            }
            else {
                echo str_pad('',4096)."\n";
                flush();
            }

            $manager->removeOverdueTab();

            if ($seconds == $waiting) {
                break;
            }

            $seconds++;
            sleep(1);
        }

        echo str_pad('',4096)."\n";
        flush();
    }
}
