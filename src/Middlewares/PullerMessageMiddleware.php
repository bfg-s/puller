<?php

namespace Bfg\Puller\Middlewares;

use Bfg\Puller\Core\CacheManager;
use Bfg\Puller\Core\Shutdown;
use Closure;

class PullerMessageMiddleware
{
    static string $guard = "web";

    static bool $isRedis = false;

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
        if (!$request->hasHeader('Puller-KeepAlive')) {

            abort(404);
        }

        header('Cache-Control: no-cache');

        if (!$guard) $guard = "web";

        PullerMessageMiddleware::$guard = $guard;

        $authGuard = \Auth::guard($guard);

        $this->manager = \Puller::newManager(
            $guard,
            ($authGuard->id() ?: 0),
            $request->header('Puller-KeepAlive')
        );

        if ($mode == "auth" && $authGuard->guest()) {

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

        $response = $next($request);

        //$response->header('Keep-Alive', 'timeout=5, max=1000');

        return $response;
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

            if (static::$isRedis) {

                $tabs = $this->manager->redis_keys($this->manager->redis_key_user_tab("*"));

                if (count($tabs) <= 1) {

                    $this->manager->emitOnUserOfflineEvent();
                }

            } else if (!$this->manager->isHasUser()) {

                $this->manager->emitOnUserOfflineEvent();
            }
        }
    }
}
