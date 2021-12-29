<?php

namespace Bfg\Puller;

use Bfg\Puller\Commands\PullMakeCommand;
use Bfg\Puller\Controllers\PullerMessageController;
use Bfg\Puller\Core\BladeDirectiveAlpineStore;
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
        PullerMessageMiddleware::$isRedis = config('cache.default') == 'redis';

        $this->mergeConfigFrom(__DIR__ . '/../config/puller.php', 'puller');

        \Route::aliasMiddleware('puller', PullerMessageMiddleware::class);

        \Route::macro('puller', function (string $guard = null, bool $authorized = null) {
            $guard = $guard ?: config('puller.guard');
            $authorized = $authorized ?: config('puller.authorized');
            \Route::get('/puller/message', PullerMessageController::class)
                ->middleware(['web', "puller:{$guard}," . ($authorized ? 'auth' : 'all')])
                ->name('puller.message');
        });

        $this->app->singleton(Shutdown::class, function () {
            return new Shutdown;
        });

        if ($this->app->runningInConsole()) {

            $this->commands([
                PullMakeCommand::class
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

        \Blade::directive('alpineStore', [BladeDirectiveAlpineStore::class, 'directive']);
        \Blade::directive('alpineStores', [BladeDirectiveAlpineStore::class, 'manyDirective']);

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
    }

    public function writeCreatedJobs()
    {
        DispatchManager::fireQueue();
    }
}
