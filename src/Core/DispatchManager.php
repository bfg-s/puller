<?php

namespace Bfg\Puller\Core;

use Bfg\Puller\Controllers\PullerController;
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
    protected ?int $user = null;

    /**
     * Apply methods for worker class
     * @var array
     */
    protected array $methods = [];

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

    /**
     * Dispatch worker to selected tab
     * @param  null  $tab
     * @param ...$arguments
     * @return bool
     */
    public function totab($tab = null, ...$arguments): bool
    {
        $tab = $tab === null ? \Puller::myTab() : $tab;
        if ($data = $this->makeTaskObjectDispatcher($arguments)) {
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
        if ($data = $this->makeTaskObjectDispatcher($arguments)) {
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
        if ($data = $this->makeTaskObjectDispatcher($arguments)) {
            foreach (\Puller::identifications() as $id) {
                if (static::canDispatchImmediately()) {
                    $manager = $data->user($id)->manager();
                    $serialize = $data->serialize();
                    $exclude[] = PullerController::addQueue($serialize,
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
                $manager = \Puller::setGuard($guard)->setUserId($user)->manager();
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
