<?php

namespace Bfg\Puller\Controllers;

use Bfg\Puller\Core\Dehydrator;
use Bfg\Puller\Middlewares\PullerMessageMiddleware;
use Bfg\Puller\Pull;
use Illuminate\Http\Request;

class PullerMessageController extends PullerController
{
    static bool $run = false;

    static array $queue = [];

    /**
     * @throws \ReflectionException
     */
    public function __invoke($name, Request $request)
    {
        $guard = PullerMessageMiddleware::$guard;
        $events = app('events');
        $property = new \ReflectionProperty($events, "listeners");
        $property->setAccessible(true);
        $eventList = array_keys(
            $property->getValue($events)
        );
        $eventPattern = "*\\" . \Str::of($name)
            ->prepend($guard, 'Message', \Str::contains($name, ":") ? '-' : ':')
            ->append("Event")
            ->camel()
            ->explode(':')
            ->map(function ($i) { return ucfirst($i); })
            ->join('\\');

        foreach ($eventList as $eventClass) {

            PullerMessageController::$run = true;

            if (\Str::is($eventPattern, $eventClass)) {
                $results = array_filter(array_merge($this->callEvent($eventClass, $request)));
                if ($results) {
                    $this->applyTasks($results);
                }
            }
        }

        if (PullerMessageController::$queue) {

            $this->applyTasks(PullerMessageController::$queue);
        }

        return $this->response();
    }

    protected function applyTasks(array $tasks)
    {
        Dehydrator::collection($tasks, function (Dehydrator $dehydrator, $key) {
            unset(PullerMessageController::$queue[$key]);
            $this->states = array_merge($this->states, $dehydrator->states);
            $this->results[] = $dehydrator->response();
        });

        if (count(PullerMessageController::$queue)) {

            $this->applyTasks(PullerMessageController::$queue);
        }

//        return static::parseTasks($tasks, function ($name, $result, Pull $pull) {
//            return $name ? ['name' => $name, 'detail' => $result] : null;
//        });
    }

    protected function callEvent(string $class, Request $request)
    {
        return event(
            app($class, $request->all())
        );
    }
}
