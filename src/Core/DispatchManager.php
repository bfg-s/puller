<?php

namespace Bfg\Puller\Core;

use Bfg\Puller\Controllers\PullerController;
use Bfg\Puller\Controllers\PullerMessageController;
use Bfg\Puller\Task;
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
    protected ?int $user = null;

    /**
     * Apply methods for worker class
     * @var array
     */
    protected array $methods = [];

    /**
     * Apply arguments for task class construct
     * @var array|null
     */
    protected ?array $arguments = null;

    /**
     * Identifier for managing managers in queue.
     * @var int|null
     */
    public ?int $id = null;

    protected ?array $rememberDispatchType = null;

    /**
     * @param  string|T  $class
     * @param  int|null  $user
     */
    public function __construct(
        string $class,
        ?int $user = null
    ) {
        $this->class = $class;
        $this->user = $user;
        $this->id = MoveZone::register($this);
    }

    /**
     * Select user for dispatch
     * @param $user
     * @return static|T
     */
    public function user($user): DispatchManager
    {
        if ($user instanceof Model) {
            $user = $user->id;
        }

        if (is_numeric($user)) {
            $this->user = (int) $user;
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

    public function detail(...$arguments)
    {
        $this->arguments = $arguments;

        return $this;
    }

    protected function makeMoveDispatch(array $type, array $arguments)
    {
        if (MoveZone::has($this)) {
            if ($arguments) $this->detail(...$arguments);
            $this->rememberDispatchType = $type;
            return !$this->rememberDispatchType;
        }
        return false;
    }

    protected function moveToOriginalDispatch(string $type)
    {
        if ($this->rememberDispatchType && isset($this->rememberDispatchType[0]) && $this->rememberDispatchType[0] !== $type) {
            return $this->{$this->rememberDispatchType[0]}(...(isset($this->rememberDispatchType[1]) ? [$this->rememberDispatchType[1]]:[]));
        }
        return '_ignored';
    }

    /**
     * Dispatch worker to selected tab
     * @param  null  $tab
     * @param ...$arguments
     * @return bool
     */
    public function totab($tab = null, ...$arguments): bool
    {
        if ($this->makeMoveDispatch(['totab', $tab], $arguments)) {
            return false;
        } else if ($redirect = $this->moveToOriginalDispatch('totab')) {
            if ($redirect !== '_ignored') {
                return $redirect;
            }
            MoveZone::forget($this);
        }

        $tab = $tab === null ? \Puller::myTab() : $tab;
        if ($data = $this->makeTaskObjectDispatcher($this->arguments ?? $arguments)) {
            /** @var CacheManager $manager */
            $manager = $data->tab($tab)->manager();
            $serialize = $data->serialize();
            $exclude = PullerController::addQueue($serialize,
                $manager->tab && $manager->tab == \Puller::myTab())
                ? [$manager->tab] : [];
            if ($manager->isHasTab() && $manager->isHasUser()) {
                return $manager->setTabTask($serialize, (array) $tab, $exclude);
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
    public function flow(...$arguments): bool
    {
        return $this->totab(
            null,
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
        if ($this->makeMoveDispatch(['stream'], $arguments)) {
            return false;
        } else if ($redirect = $this->moveToOriginalDispatch('stream')) {
            if ($redirect !== '_ignored') {
                return $redirect;
            }
            MoveZone::forget($this);
        }

        if ($data = $this->makeTaskObjectDispatcher($this->arguments ?? $arguments)) {
            if (static::canDispatchImmediately()) {
                $manager = $data->manager();
                $serialize = $data->serialize();
                $exclude = PullerController::addQueue($serialize,
                    $manager->tab && $manager->tab == \Puller::myTab())
                    ? [$manager->tab] : [];
                if ($manager->isHasUser()) {
                    return $manager->setTabTask($serialize, null, $exclude);
                }
            } else {
                static::$queue[$data->guard][$data->user][] = $data->serialize();

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
        if ($this->makeMoveDispatch(['flux'], $arguments)) {
            return false;
        } else if ($redirect = $this->moveToOriginalDispatch('flux')) {
            if ($redirect !== '_ignored') {
                return $redirect;
            }
            MoveZone::forget($this);
        }

        if ($data = $this->makeTaskObjectDispatcher($this->arguments ?? $arguments)) {
            foreach (\Puller::identifications() as $id) {
                if (static::canDispatchImmediately()) {
                    $manager = $data->user($id)->manager();
                    $serialize = $data->serialize();
                    $exclude = PullerController::addQueue($serialize,
                        $manager->tab && $manager->tab == \Puller::myTab()) ?
                        [$manager->tab] : [];
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
        return Hydrator::from($this->class, $arguments, function (Hydrator $hydrator) {
            $hydrator->guard($this->guard)->user($this->user)->methodsForTask($this->methods);
        });
    }

    /**
     * Send workers queue
     * @return void
     */
    public static function fireQueue()
    {
        foreach (static::$queue as $guard => $queueGuards) {
            foreach ($queueGuards as $user => $queueUserTasks) {
                $manager = \Puller::setGuard($guard)->setUser($user)->manager();
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
            || \Puller::myTab()
            || request()->ajax()
            || request()->pjax();
    }
}
