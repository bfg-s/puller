<?php

namespace Bfg\Puller;

use Bfg\Puller\Commands\PullEventsCommand;
use Bfg\Puller\Controllers\PullerController;
use Bfg\Puller\Commands\TaskMakeCommand;
use Bfg\Puller\Controllers\PullerKeepAliveController;
use Bfg\Puller\Controllers\PullerMessageController;
use Bfg\Puller\Core\DispatchManager;
use Bfg\Puller\Core\Shutdown;
use Bfg\Puller\Middlewares\PullerMessageMiddleware;
use Illuminate\Support\ServiceProvider;

class PullerServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/puller.php', 'puller');

        \Route::aliasMiddleware('puller', PullerMessageMiddleware::class);

        \Route::macro('puller', function (string $guard = null, bool $authorized = null) {
            $guard = $guard ?: config('puller.guard');
            $authorized = $authorized ?: config('puller.authorized');
            \Route::get('/puller/keep-alive', PullerKeepAliveController::class)
                ->middleware(['web', "puller:{$guard}," . ($authorized ? 'auth' : 'all')])
                ->name('puller.keep-alive');
            \Route::get('/puller/keep-verify', [PullerKeepAliveController::class, 'verify'])
                ->middleware(['web', "puller:{$guard}," . ($authorized ? 'auth' : 'all')])
                ->name('puller.keep-verify');
            \Route::post('/puller/message/{name}', PullerMessageController::class)
                ->middleware(['web', "puller:{$guard}," . ($authorized ? 'auth' : 'all')])
                ->name('puller.message');
        });

        $this->app->singleton(Shutdown::class, function () {
            return new Shutdown;
        });

        if ($this->app->runningInConsole()) {

            $this->commands([
                TaskMakeCommand::class,
                PullEventsCommand::class,
            ]);
        }
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        \Route::puller();

        $this->publishes([
            __DIR__ . '/../config/puller.php' => config_path('puller.php')
        ], 'puller-config');

        $this->publishes([
            __DIR__ . '/../assets' => public_path('vendor/puller')
        ], 'puller-assets');

        $this->publishes([
            __DIR__ . '/../assets' => public_path('vendor/puller')
        ], 'laravel-assets');

        app(Shutdown::class)
            ->registerFunction([$this, 'writeCreatedJobs']);

        if (PullerController::canBeRun()) {

            PullerController::run();
        }
    }

    public function writeCreatedJobs()
    {
        DispatchManager::fireQueue();
    }
}
