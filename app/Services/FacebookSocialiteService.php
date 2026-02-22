<?php

namespace App\Services;

use Laravel\Socialite\Facades\Socialite;

class FacebookSocialiteService
{
    /**
     * Facebook OAuth scopes for full page management and insights.
     */
    protected array $scopes = [
        'business_management',
        'email',
        'pages_manage_engagement',
        'pages_manage_metadata',
        'pages_manage_posts',
        'pages_read_engagement',
        'pages_read_user_content',
        'pages_show_list',
        'public_profile',
        'read_insights',
    ];

    /**
     * Redirect the user to Facebook for authentication.
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function redirect()
    {
        $driver = Socialite::driver('facebook')
            ->scopes($this->scopes)
            ->redirectUrl(route('facebook.callback'));

        $configId = config('services.facebook.config_id') ?? env('FACEBOOK_CONFIG_ID');
        if ($configId) {
            $driver->with(['config_id' => $configId]);
        }

        return $driver->redirect();
    }

    /**
     * Get the Socialite user from the OAuth callback.
     *
     * @return \Laravel\Socialite\Contracts\User
     */
    public function user()
    {
        return Socialite::driver('facebook')->user();
    }

    /**
     * Get the configured scopes.
     *
     * @return array<string>
     */
    public function getScopes(): array
    {
        return $this->scopes;
    }
}
