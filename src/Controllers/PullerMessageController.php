<?php

namespace Bfg\Puller\Controllers;

use Bfg\Puller\Core\Dehydrator;
use Bfg\Puller\Core\LaravelHooks;
use Bfg\Puller\Middlewares\PullerMessageMiddleware;
use Illuminate\Http\Request;

class PullerMessageController extends PullerController
{
    /**
     * @throws \ReflectionException
     */
    public function __invoke($name, Request $request)
    {
        $eventPattern = "*\\" . \Str::of($name)
            ->prepend(\Puller::currentGuard(), 'Message', \Str::contains($name, ":") ? '-' : ':')
            ->camel()
            ->explode(':')
            ->map(function ($i) { return ucfirst($i); })
            ->join('\\');

        foreach (LaravelHooks::getEvents() as $eventClass) {
            if (\Str::is($eventPattern, $eventClass) || \Str::is($eventPattern . "Event", $eventClass)) {
                $results = array_filter($this->callEvent($eventClass, $request));
                if ($results) {
                    $this->applyTasks($results);
                }
            }
        }

        if (static::hasQueue()) {

            $this->applyTasks(static::getQueue());
        }

        return $this->response();
    }

    protected function callEvent(string $class, Request $request)
    {
        $event = app($class, $request->all());

        if (method_exists($event, 'access') && !$event->access()) {
            return null;
        }

        return event(
            $event
        );
    }

    protected function applyTasks(array $tasks)
    {
        Dehydrator::collection($tasks, function (Dehydrator $dehydrator, $key) {
            static::forgetQueue($key);
            $this->states = array_merge($this->states, $dehydrator->states);
            $this->results[] = $dehydrator->response();
        });

        if (static::hasQueue()) {
            $this->applyTasks(static::getQueue());
        }
    }
}
