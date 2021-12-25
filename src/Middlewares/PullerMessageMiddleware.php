<?php

namespace Bfg\Puller\Middlewares;

use Bfg\Puller\Core\CacheManager;
use Bfg\Puller\Core\Shutdown;
use Closure;

class PullerMessageMiddleware
{
    /**
     * @var CacheManager
     */
    public $manager;

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  Closure  $next
     * @param  string  $guard
     * @param  string  $mode
     * @return mixed
     */
    public function handle($request, Closure $next, string $guard = "web", string $mode = "auth")
    {
        if (!$guard) $guard = "web";

        $authGuard = \Auth::guard($guard);

        $this->manager = \Puller::newManager(
            $guard,
            ($authGuard->id() ?: 0),
            $request->route('tab_hash')
        );

        if ($mode == "auth" && $authGuard->guest()) {

            abort(404);
        }

        set_time_limit(config('puller.waiting', 30));

        app(Shutdown::class)
            ->registerFunction([$this, 'mishandle']);

        if (!$this->manager->isHasUser()) {
            $this->manager->emitOnUserOnlineEvent();
        }
        if (!$this->manager->isHasTab()) {
            $this->manager->emitOnNewTabEvent();
        }

        $this->manager->checkTab()
            ->checkUser();

        return $next($request);
    }

    /**
     * Shutdown function
     * @return void
     */
    public function mishandle()
    {
        if (connection_aborted()) {

            $this->manager->removeTab()
                ->removeUser();

            $this->manager->emitOnCloseTabEvent();

            if (!$this->manager->isHasUser()) {

                $this->manager->emitOnUserOfflineEvent();
            }
        }
    }
}
