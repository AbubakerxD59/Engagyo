<?php

namespace App\Services;

use Illuminate\Support\Facades\Session;
use Laravel\Socialite\Facades\Socialite;

class FacebookSocialiteService
{
    /**
     * Facebook OAuth scopes for full page management and insights.
     * Ordered by dependency: base permissions first, then permissions that depend on them.
     * (public_profile, email, pages_show_list have no deps; others follow Facebook's dependency chain)
     */
    protected array $scopes = [
        'read_insights',
        'pages_show_list',
        'business_management',
        'pages_read_engagement',
        'pages_manage_metadata',
        'pages_read_user_content',
        'pages_manage_posts',
        'pages_manage_engagement',
    ];

    /**
     * Scopes for connecting Instagram Business accounts (still Facebook OAuth).
     */
    protected array $instagramScopes = [
        'pages_show_list',
        'business_management',
        'pages_read_engagement',
        'instagram_basic',
        'instagram_content_publish',
        'instagram_manage_insights',
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
        Session::forget('facebook_oauth_intent');

        return Socialite::driver('facebook')
            ->scopes($this->scopes)
            ->with([
                'auth_type' => 'rerequest',
            ])
            ->redirectUrl(route('facebook.callback'))
            ->redirect();
    }

    /**
     * Facebook OAuth with Instagram-related permissions; callback branches on session intent.
     */
    public function redirectForInstagram()
    {
        Session::put('facebook_oauth_intent', 'instagram');

        return Socialite::driver('facebook')
            ->scopes($this->instagramScopes)
            ->with([
                'auth_type' => 'rerequest',
            ])
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
