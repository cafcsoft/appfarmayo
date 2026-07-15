<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

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
        if (app()->isProduction()) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        Schema::defaultStringLength(191);

        $this->configureDefaults();

        Gate::define('access-admin-reports', function ($user) {
            if ($user->is_customer) {
                return false;
            }

            if ($user->roles()->where('name', 'contador')->exists()) {
                return false;
            }

            return true;
        });

        Gate::define('access-accounting', function ($user) {
            return $user->roles()->whereIn('name', ['contador', 'Administrador'])->exists();
        });

        Gate::define('manage-users', function ($user) {
            return $user->roles()->whereIn('name', ['Administrador', 'Developer'])->exists();
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
