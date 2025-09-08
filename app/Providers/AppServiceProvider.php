<?php

namespace App\Providers;

use App\Models\Administration\Permission;
use App\Models\Administration\Role;
use App\Models\Administration\Tenant;
use App\Models\Administration\User;
use App\Observers\AuditableObserver;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

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
        Passport::tokensExpireIn(now()->addDays(15));
        Passport::refreshTokensExpireIn(now()->addDays(30));
        Passport::personalAccessTokensExpireIn(now()->addMonths(6));

        // Registra observers para cada modelo que quieras auditar
        User::observe(AuditableObserver::class);
        Role::observe(AuditableObserver::class);
        Permission::observe(AuditableObserver::class);
        Tenant::observe(AuditableObserver::class);

        // Añade aquí tus futuros modelos...
    }
}
