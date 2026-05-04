<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Log;

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
        $this->ensureFrameworkStoragePathsExist();

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

    /**
     * Ensure Laravel framework runtime directories always exist.
     */
    private function ensureFrameworkStoragePathsExist(): void
    {
        $paths = [
            storage_path('framework/cache/data'),
            storage_path('framework/sessions'),
            storage_path('framework/views'),
        ];

        foreach ($paths as $path) {
            if (is_dir($path)) {
                continue;
            }

            try {
                @mkdir($path, 0775, true);
            } catch (\Throwable $e) {
                Log::warning('Failed to create framework storage path.', [
                    'path' => $path,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
