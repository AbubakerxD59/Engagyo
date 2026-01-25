<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;

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
        // Custom Blade directive to check if user can use a feature
        Blade::if('canUseFeature', function ($featureKey = null) {
            $user = User::find(auth()->user()->id);
            if (!$user) {
                return false;
            }
            return $user->canUseFeature($featureKey);
        });
        // Custom Blade directive to check if user can access a menu
        Blade::if('canAccessMenu', function ($menuId = null) {
            $user = User::find(auth()->user()->id);
            if (!$user) {
                return false;
            }
            return $user->canAccessMenu($menuId);
        });
    }
}
