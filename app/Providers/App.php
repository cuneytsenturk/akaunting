<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Vite;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider as Provider;
use Laravel\Sanctum\Sanctum;

class App extends Provider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        if (config('app.installed') && config('app.debug')) {
            $this->app->register(\Barryvdh\Debugbar\ServiceProvider::class);
        }

        if (! env_is_production()) {
            $this->app->register(\Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider::class);
        }

        Sanctum::ignoreMigrations();
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Laravel db fix
        Schema::defaultStringLength(191);

        Paginator::useBootstrap();

        Model::preventLazyLoading(config('app.eager_load'));

        Model::handleLazyLoadingViolationUsing(function ($model, $relation) {
            if (config('logging.default') == 'sentry') {
                \Sentry\Laravel\Integration::lazyLoadingViolationReporter();
            } else {
                $class = get_class($model);

                report("Attempted to lazy load [{$relation}] on model [{$class}].");
            }
        });

        // Every hand-written asset() call across this codebase (Mix-era and
        // otherwise) includes an explicit "public/" path segment, because
        // this app's docroot is the project root, not public/ — unlike a
        // stock Laravel install (which Vite's own URL generation assumes).
        // Without this, Vite::asset()/the manifest-driven <script>/<link>
        // tags resolve to "/build/..." instead of "/public/build/...",
        // 404ing everywhere. Only the URL resolver changes; the manifest
        // file lookup itself still correctly reads public_path('build/...').
        $this->app->make(Vite::class)->createAssetPathsUsing(
            fn ($path, $secure = null) => asset('public/' . $path, $secure)
        );
    }
}
