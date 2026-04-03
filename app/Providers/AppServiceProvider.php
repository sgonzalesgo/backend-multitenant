<?php

namespace App\Providers;

use App\Models\Administration\Permission;
use App\Models\Administration\Role;
use App\Models\Administration\Tenant;
use App\Models\Administration\User;
use App\Observers\AuditableObserver;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;

class AppServiceProvider extends ServiceProvider
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
        User::observe(AuditableObserver::class);
        Role::observe(AuditableObserver::class);
        Permission::observe(AuditableObserver::class);
        Tenant::observe(AuditableObserver::class);

        RateLimiter::for('refresh', function (Request $request) {
            return Limit::perMinute(10)->by(
                $request->cookie(config('auth.refresh_cookie', 'refresh_token'))
                    ?: $request->ip()
            );
        });

        // para la autenticación de websockets
//        Broadcast::routes([
//            'middleware' => ['bearer_cookie', 'auth:api'],
//        ]);

        require base_path('routes/channels.php');
    }
}
