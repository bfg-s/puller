<?php

namespace Bfg\Puller\Controllers;

use Bfg\Puller\Core\Shutdown;
use Bfg\Puller\Pull;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class PullerMessageController
{
    public function test($a, $b)
    {
        info($a);
        info($b);
    }

    protected function redisEngine()
    {
        while (ob_get_level()){
            ob_get_contents();
            ob_end_clean();
        }

        $results = [];

        $seconds = 0;
        $waiting = config('puller.waiting', 30);
        $manager = \Puller::manager();

        $manager->removeOverdueTab();
        $manager->tabTouchCreated();
        $manager->tabTouch();

        Redis::connection()->command('psubscribe', [[\request()->header('Puller-KeepAlive') . '.*'], function ($a, $b) {
            info($a);
            info($b);
        }]);


        return "";

        try {
            Redis::psubscribe([\request()->header('Puller-KeepAlive') . '.*'], function ($message, $channel) use (&$results) {
                $results[] = ['name' => $message, 'detail' => $channel];
                Redis::client()->close();
            });
        } catch (\RedisException $redisException) {
            app(Shutdown::class)
                ->forgetFunction('disconnect');
        }

//        while (!connection_aborted()) {
//
//            $manager->tabTouch();
//
//            echo str_pad('',4096)."\n";
//            flush();
//        }

//        Redis::client()->close();

        echo str_pad('',4096)."\n";
        flush();

        return $results;
    }

    public function __invoke(Request $request)
    {
//        if (config('cache.default') == 'redis') {
//
//            return $this->redisEngine();
//        }

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
