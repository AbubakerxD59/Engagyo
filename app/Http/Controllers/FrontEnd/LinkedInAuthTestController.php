<?php

namespace App\Http\Controllers\FrontEnd;

use App\Http\Controllers\Controller;
use App\Services\HttpService;
use Illuminate\Http\Request;

class LinkedInAuthTestController extends Controller
{
    public function index()
    {
        $steps = session('linkedin_test_steps', []);
        session()->forget('linkedin_test_steps');

        return view('frontend.linkedin-auth-test', [
            'steps' => $steps,
        ]);
    }

    public function start(Request $request)
    {
        $clientId = (string) config('services.linkedin.client_id');
        $clientSecret = (string) config('services.linkedin.client_secret');
        $redirectUri = route('linkedin.callback');
        $state = bin2hex(random_bytes(16));

        $steps = [
            [
                'step' => 'config_check',
                'response' => [
                    'client_id_present' => $clientId !== '',
                    'client_secret_present' => $clientSecret !== '',
                    'redirect_uri' => $redirectUri,
                ],
            ],
        ];

        if ($clientId === '' || $clientSecret === '') {
            $steps[] = [
                'step' => 'error',
                'response' => [
                    'message' => 'LinkedIn OAuth is not configured. Set LINKEDIN_CLIENT_ID and LINKEDIN_CLIENT_SECRET.',
                ],
            ];
            session(['linkedin_test_steps' => $steps]);

            return redirect()->route('linkedin.test.index');
        }

        session([
            'linkedin_test_oauth_state' => $state,
        ]);

        $query = http_build_query([
            'response_type' => 'code',
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'scope' => 'openid profile email',
            'state' => $state,
        ], '', '&', PHP_QUERY_RFC3986);

        $authorizeUrl = 'https://www.linkedin.com/oauth/v2/authorization?'.$query;

        $steps[] = [
            'step' => 'authorize_redirect',
            'response' => [
                'url' => $authorizeUrl,
            ],
        ];
        session(['linkedin_test_steps' => $steps]);

        return redirect()->away($authorizeUrl);
    }

    public function callback(Request $request)
    {
        $steps = session('linkedin_test_steps', []);

        $steps[] = [
            'step' => 'callback_query',
            'response' => $request->query(),
        ];

        $expectedState = (string) session('linkedin_test_oauth_state', '');
        $incomingState = (string) $request->query('state', '');

        $steps[] = [
            'step' => 'state_validation',
            'response' => [
                'expected_state' => $expectedState,
                'incoming_state' => $incomingState,
                'valid' => $expectedState !== '' && hash_equals($expectedState, $incomingState),
            ],
        ];

        if ($expectedState === '' || ! hash_equals($expectedState, $incomingState)) {
            $steps[] = [
                'step' => 'error',
                'response' => [
                    'message' => 'Invalid OAuth state.',
                ],
            ];
            session()->forget('linkedin_test_oauth_state');
            session(['linkedin_test_steps' => $steps]);

            return redirect()->route('linkedin.test.index');
        }

        $code = (string) $request->query('code', '');
        if ($code === '') {
            $steps[] = [
                'step' => 'error',
                'response' => [
                    'message' => 'Missing authorization code.',
                ],
            ];
            session()->forget('linkedin_test_oauth_state');
            session(['linkedin_test_steps' => $steps]);

            return redirect()->route('linkedin.test.index');
        }

        $http = new HttpService();
        $tokenResponse = $http->post('https://www.linkedin.com/oauth/v2/accessToken', [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => route('linkedin.test.callback'),
            'client_id' => (string) config('services.linkedin.client_id'),
            'client_secret' => (string) config('services.linkedin.client_secret'),
        ], [
            'Content-Type' => 'application/x-www-form-urlencoded',
        ]);

        $steps[] = [
            'step' => 'access_token_response',
            'response' => $tokenResponse,
        ];

        $accessToken = (string) ($tokenResponse['access_token'] ?? '');
        if ($accessToken !== '') {
            $userInfoResponse = $http->get('https://api.linkedin.com/v2/userinfo', [], [
                'Authorization' => 'Bearer '.$accessToken,
            ]);

            $steps[] = [
                'step' => 'userinfo_response',
                'response' => $userInfoResponse,
            ];
        } else {
            $steps[] = [
                'step' => 'userinfo_response',
                'response' => [
                    'skipped' => true,
                    'reason' => 'No access token returned from token exchange.',
                ],
            ];
        }

        session()->forget('linkedin_test_oauth_state');
        session(['linkedin_test_steps' => $steps]);

        return redirect()->route('linkedin.test.index');
    }
}

