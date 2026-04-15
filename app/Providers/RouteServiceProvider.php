<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Cache\RateLimiting\Limit;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();
    }

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
