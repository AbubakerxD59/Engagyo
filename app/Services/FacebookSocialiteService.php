<?php

namespace App\Services;

use Laravel\Socialite\Facades\Socialite;

class FacebookSocialiteService
{
    /**
     * Facebook OAuth scopes for full page management and insights.
     * Ordered by dependency: base permissions first, then permissions that depend on them.
     * (public_profile, email, pages_show_list have no deps; others follow Facebook's dependency chain)
     */
    protected array $scopes = [
        'public_profile',
        'email',
        'pages_show_list',
        'pages_read_engagement',
        'pages_read_user_content',
        'pages_manage_metadata',
        'read_insights',
        'pages_manage_posts',
        'pages_manage_engagement',
        'business_management',
    ];

    /**
     * Redirect the user to Facebook for authentication.
     *
     * Note: Do not pass config_id here. When config_id (Facebook Login for Business) is used,
     * it overrides the scope parameter and only requests permissions defined in that Meta
     * configuration. To request our full permission set (including pages_show_list for
     * connecting business pages), we rely on the scope parameter alone.
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function redirect()
    {
        return Socialite::driver('facebook')
            ->scopes($this->scopes)
            ->with(['auth_type' => 'rerequest'])
            ->redirectUrl(route('facebook.callback'))
            ->redirect();
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
