<?php

namespace Bfg\Puller\Middlewares;

use Bfg\Puller\Core\CacheManager;
use Bfg\Puller\Core\Shutdown;
use Bfg\Puller\Core\Trap;
use Closure;

class PullerMessageMiddleware
{
    static int $tabLifetime = 3;

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
        header('Cache-Control: no-cache');

        if (!$guard) $guard = "web";

        if (!$request->hasHeader('Puller-KeepAlive')) {

            abort(404);
        }

        $authGuard = \Auth::guard($guard);

        $this->manager = \Puller::setGuard($guard)->manager();

        if ($mode == "auth" && $authGuard->guest()) {

            abort(404);
        }

        if (!\Puller::myTab()) {

            abort(404);
        }

        set_time_limit(config('puller.waiting', 30));

        app(Shutdown::class)
            ->registerFunction([$this, 'mishandle'], 'disconnect');

        $online = !$this->manager->isHasUser();
        $newTab = !$this->manager->isHasTab();

        $this->manager->checkTab()
            ->checkUser();

        if ($online) {
            $this->manager->emitOnUserOnlineEvent();
        }

        if ($newTab) {
            $this->manager->emitOnNewTabEvent($online);
        }

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
