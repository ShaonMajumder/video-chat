<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * This is used by Laravel authentication to redirect users after login.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * The controller namespace for the application.
     *
     * When present, controller route declarations will automatically be prefixed with this namespace.
     *
     * @var string|null
     */
    // protected $namespace = 'App\\Http\\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            Route::prefix('api')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/web.php'));
        });
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(3)->by($request->ip())->response(function () {
                return response()->json(['error' => 'Too many login attempts. Please wait a minute.'], 429);
            });
        });

        RateLimiter::for('chat', function (Request $request) {
            // $userId = $request->user('api')?->id ?? $request->ip();
            $userId = optional($request->user('api'))->id ?? $request->ip();
            return Limit::perMinute(30)->by($userId);
        });
        
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by(optional($request->user())->id ?: $request->ip());
        });

        // Global rate limit
        RateLimiter::for('global', function (Request $request) {
            return Limit::perMinute(100000)->by('global')->response(function () {
                return response()->json(['error' => 'Server busy. Please try again later.'], 429);
            });
        });
    }
}
