<?php

namespace Bfg\Puller\Core;

use Bfg\Puller\Controllers\PullerMessageController;
use Bfg\Puller\Pull;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Redis;

/**
 * @template T
 */
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
    protected ?int $for_id = null;

    /**
     * Apply methods for worker class
     * @var array
     */
    protected array $methods = [];

    /**
     * @param  string|T  $class
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
     * @return static|T
     */
    public function for($user): DispatchManager
    {
        if ($user instanceof Model) {
            $user = $user->id;
        }

        if (is_numeric($user)) {
            $this->for_id = (int) $user;
        }

        return $this;
    }

    /**
     * Select guard for user dispatch
     * @param  string  $guard
     * @return static|T
     */
    public function guard(string $guard): DispatchManager
    {
        $this->guard = $guard;

        return $this;
    }

    /**
     * Dispatch worker to selected tab
     * @param  null  $tab
     * @param ...$arguments
     * @return bool
     */
    public function totab($tab = null, ...$arguments): bool
    {
        if ($data = $this->makeTaskObjectDispatcher($arguments)) {
            /** @var CacheManager $manager */
            $manager = $data->manager(null, null, $tab);
            $exclude = [];
            $serialize = $data->serialize();
            if (PullerMessageController::$run && $manager->tab && $manager->tab == \Puller::myTab()) {
                $exclude[] = $manager->tab;
                PullerMessageController::$queue[] = $serialize;
            }
            if ($manager->isHasTab() && $manager->isHasUser()) {
                return $manager->setTabTask($serialize, (array)$tab, $exclude);
            }
        }

        return false;
    }

    /**
     * flow
     * Dispatch worker to current tab
     * @param ...$arguments
     * @return bool
     */
    public function flow(...$arguments): bool {

        return $this->totab(
            \Puller::myTab(),
            ...$arguments
        );
    }

    /**
     * stream
     * Dispatch worker
     * @param ...$arguments
     * @return bool
     */
    public function stream(...$arguments): bool
    {
        if ($data = $this->makeTaskObjectDispatcher($arguments)) {

            if (static::canDispatchImmediately()) {

                $manager = $data->manager();

                $exclude = [];

                $serialize = $data->serialize();

                if (PullerMessageController::$run && $manager->tab && $manager->tab == \Puller::myTab()) {
                    $exclude[] = $manager->tab;
                    PullerMessageController::$queue[] = $serialize;
                }

                if ($manager->isHasUser()) {

                    return $manager->setTabTask($serialize, null, $exclude);
                }

            } else {

                static::$queue[$data->guard][$data->for_id][] = $data->serialize();

                return true;
            }
        }

        return false;
    }

    /**
     * flux
     * Dispatch worker for everyone online user
     * @param ...$arguments
     * @return bool
     */
    public function flux(...$arguments): bool
    {
        if ($data = $this->makeTaskObjectDispatcher($arguments)) {

            foreach (\Puller::identifications() as $id) {

                if (static::canDispatchImmediately()) {
                    $manager = $data->manager(null, $id);
                    $exclude = [];
                    $serialize = $data->serialize();
                    if (PullerMessageController::$run && $manager->tab && $manager->tab == \Puller::myTab()) {
                        $exclude[] = $manager->tab;
                        PullerMessageController::$queue[] = $serialize;
                    }
                    $manager->setTabTask($data->serialize(), null, $exclude);

                } else {

                    static::$queue[$data->guard][$id][] = $data->serialize();
                }
            }

            return true;
        }

        return false;
    }

    /**
     * Task object maker before dispatch
     * @param $arguments
     * @return object|null
     */
    protected function makeTaskObjectDispatcher(
        $arguments
    ) {
        $pullObject = new $this->class(...$arguments);

        if ($pullObject instanceof Pull) {
            foreach ($this->methods as $method) {

                if (isset($method['name']) && isset($method['arguments'])) {

                    $pullObject->{$method['name']}(...$method['arguments']);
                }
            }

            $guard = $this->guard ?: $pullObject->getGuard();
            $for_id = $this->for_id !== null ? $this->for_id
                : ($pullObject->getForId() !== null ? $pullObject->getForId() : null);

            return new Hydrator($pullObject, $guard, $for_id);
        }

        return null;
    }

    /**
     * Send workers queue
     * @return void
     */
    public static function fireQueue()
    {
        foreach (static::$queue as $guard => $queueGuards) {
            foreach ($queueGuards as $for_id => $queueUserTasks) {
                $manager = \Puller::setGuard($guard)->setUserId($for_id)->manager();
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
     * @return static|T
     */
    public function __call($name, $arguments)
    {
        $this->methods[] = ['name' => $name, 'arguments' => $arguments];

        return $this;
    }

    /**
     * Is can dispatch immediately
     * @return bool
     */
    public static function canDispatchImmediately(): bool
    {
        return app()->runningInConsole()
            || request()->hasHeader('Puller-KeepAlive')
            || request()->ajax()
            || request()->pjax();
    }
}
