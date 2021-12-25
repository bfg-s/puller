<?php

namespace Bfg\Puller\Core;

use Bfg\Puller\Pull;
use Illuminate\Database\Eloquent\Model;

class DispatchManager
{
    /**
     * Queue of pulls for executing
     * @var array
     */
    protected static array $queue = [];

    /**
     * Class for dispatch work
     * @var string|null
     */
    protected string $class;

    /**
     * Auth user guard
     * @var string|null
     */
    protected ?string $guard = null;

    /**
     * User recipient
     * @var int|null
     */
    protected ?int $for_id;

    /**
     * Apply methods for worker class
     * @var array
     */
    protected array $methods = [];

    /**
     * @param  string  $class
     * @param  int|null  $for_id
     */
    public function __construct(
        string $class,
        ?int $for_id = null
    ) {
        $this->class = $class;
        $this->for_id = $for_id;
    }

    /**
     * Select user for dispatch
     * @param $user
     * @return $this
     */
    public function for($user): DispatchManager
    {
        if ($user instanceof Model) {
            $user = $user->id;
        }

        $this->for_id = $user;

        return $this;
    }

    /**
     * Select guard for user dispatch
     * @param  string  $guard
     * @return $this
     */
    public function guard(string $guard): DispatchManager
    {
        $this->guard = $guard;

        return $this;
    }

    /**
     * Dispatch worker
     * @param ...$arguments
     * @return bool
     */
    public function dispatch(...$arguments): bool
    {
        $pullObject = new $this->class(...$arguments);

        if ($pullObject instanceof Pull) {

            foreach ($this->methods as $method) {

                if (isset($method['name']) && isset($method['arguments'])) {

                    $pullObject->{$method['name']}(...$method['arguments']);
                }
            }

            $guard = $this->guard ?: $pullObject->getGuard();

            $for_id = $this->for_id !== null ? $this->for_id
                : ($pullObject->getForId() !== null ? $pullObject->getForId() : 0);

            $hydratedObject = serialize($pullObject);

            if (app()->runningInConsole()) {

                $manager = \Puller::newManager($guard, $for_id);

                if ($manager->isHasUser()) {

                    $manager->setTabTask($hydratedObject);

                    return true;
                }

            } else {

                static::$queue[$guard][$for_id][] = $hydratedObject;

                return true;
            }
        }

        return false;
    }

    /**
     * Send workers queue
     * @return void
     */
    public static function fireQueue()
    {
        foreach (static::$queue as $guard => $queueGuards) {
            foreach ($queueGuards as $for_id => $queueUserTasks) {
                $manager = \Puller::newManager($guard, $for_id);
                if ($manager->isHasUser()) {
                    $manager->setTabTask($queueUserTasks);
                }
            }
        }
    }

    /**
     * Trap rof worker filling
     * @param $name
     * @param $arguments
     * @return $this
     */
    public function __call($name, $arguments)
    {
        $this->methods[] = ['name' => $name, 'arguments' => $arguments];

        return $this;
    }
}
