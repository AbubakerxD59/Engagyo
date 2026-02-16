<?php

namespace App\Http\Controllers;

use App\Models\DomainUtmCode;
use App\Models\Feature;
use App\Models\ShortLink;
use App\Models\User;
use App\Services\FeatureUsageService;
use App\Services\HtmlParseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class GeneralController extends Controller
{
    public function __construct(protected FeatureUsageService $featureUsageService) {}
    /**
     * Public URL shortening (no signup required).
     * Anonymous users are tracked by user_agent (and IP).
     * If the same URL was already shortened by this user (or same anonymous identity), returns that short link.
     */
    public function shortenPublic(Request $request)
    {
        $request->validate([
            'original_url' => 'required|url|max:2048',
        ]);

        $normalizedUrl = ShortLink::normalizeUrl($request->original_url);
        $userId = Auth::guard('user')->id();
        $userAgent = $request->input('user_agent') ?: $request->userAgent();
        $userAgent = $userAgent ? substr($userAgent, 0, 65535) : null;
        $ipAddress = $request->ip();

        $existing = $this->findExistingShortLink($normalizedUrl, $userId, $userAgent);

        if ($existing) {
            return response()->json([
                'success' => true,
                'short_url' => url('/s/' . $existing->short_code),
                'short_code' => $existing->short_code,
                'original_url' => $existing->original_url,
                'existing' => true,
            ]);
        }

        $shortCode = ShortLink::generateUniqueCode(6);

        ShortLink::create([
            'user_id' => $userId,
            'short_code' => $shortCode,
            'original_url' => $normalizedUrl,
            'user_agent' => $userAgent,
            'ip_address' => $ipAddress,
        ]);

        return response()->json([
            'success' => true,
            'short_url' => url('/s/' . $shortCode),
            'short_code' => $shortCode,
            'original_url' => $normalizedUrl,
            'existing' => false,
        ]);
    }

    /**
     * Find an existing short link for this URL and user (or anonymous identity).
     */
    protected function findExistingShortLink(string $normalizedUrl, $userId, ?string $userAgent): ?ShortLink
    {
        if ($userId !== null) {
            return ShortLink::where('user_id', $userId)
                ->where(function ($q) use ($normalizedUrl) {
                    $q->where('original_url', $normalizedUrl);
                })
                ->first();
        }

        if ($userAgent !== null && $userAgent !== '') {
            return ShortLink::whereNull('user_id')
                ->where('user_agent', $userAgent)
                ->where('original_url', $normalizedUrl)
                ->first();
        }

        return ShortLink::whereNull('user_id')
            ->whereNull('user_agent')
            ->where('original_url', $normalizedUrl)
            ->first();
    }

    /**
     * Store pending URL tracking data in session (for anonymous users who will sign in/up).
     */
    public function savePendingUrlTracking(Request $request)
    {
        $request->validate([
            'domain_name' => 'required|string|max:255',
            'utm_codes' => 'required|array',
            'utm_codes.*.key' => 'required|string|max:255',
            'utm_codes.*.value' => 'nullable|string|max:255',
        ]);

        $domainName = $this->normalizeDomainName($request->domain_name);
        $utmCodes = $request->utm_codes;

        foreach ($utmCodes as $index => $utmCodeData) {
            if (isset($utmCodeData['key']) && $utmCodeData['key'] === 'utm_source') {
                $utmCodes[$index]['value'] = 'Engagyo';
                break;
            }
        }
        if (!collect($utmCodes)->contains('key', 'utm_source')) {
            $utmCodes[] = ['key' => 'utm_source', 'value' => 'Engagyo'];
        }

        Session::put('pending_url_tracking', [
            'domain_name' => $domainName,
            'utm_codes' => $utmCodes,
            'created_at' => now()->toIso8601String(),
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * Set intended URL to url-tracking after auth, then redirect to login.
     */
    public function setIntendedAndShowLogin()
    {
        Session::put('url.intended', route('general.urlTrackingAfterAuth'));
        return redirect()->route('frontend.showLogin');
    }

    /**
     * Set intended URL to url-tracking after auth, then redirect to register.
     */
    public function setIntendedAndShowRegister()
    {
        Session::put('url.intended', route('general.urlTrackingAfterAuth'));
        return redirect()->route('frontend.showRegister');
    }

    /**
     * Apply pending URL tracking after login/register and redirect to URL Tracking tab.
     */
    public function urlTrackingAfterAuth(Request $request)
    {
        $user = Auth::guard('user')->user();
        $pending = Session::get('pending_url_tracking');

        Session::forget('pending_url_tracking');
        Session::forget('url.intended');

        if (!$pending || empty($pending['domain_name']) || empty($pending['utm_codes'])) {
            return redirect()->route('panel.url-tracking')->with('success', __('URL Tracking is ready. Add more domains anytime.'));
        }

        $domainName = $pending['domain_name'];
        $utmCodes = $pending['utm_codes'];

        $isNewDomain = !DomainUtmCode::withoutGlobalScopes()->where('user_id', $user->id)->where('domain_name', $domainName)->exists();

        if ($isNewDomain) {
            $result = $this->featureUsageService->checkAndIncrement($user, Feature::$features_list[6], 1);
            if (!$result['allowed']) {
                return redirect()->route('panel.url-tracking')->with('error', $result['message'] ?? 'URL Tracking limit reached for your plan.');
            }
        }

        foreach ($utmCodes as $utmCodeData) {
            $utmKey = $utmCodeData['key'] ?? '';
            $utmValue = $utmCodeData['value'] ?? '';
            if ($utmKey === 'utm_source') {
                $utmValue = 'Engagyo';
            }
            if (empty(trim($utmValue)) && $utmKey !== 'utm_source') {
                continue;
            }
            if ($utmKey === 'utm_source' && empty(trim($utmValue))) {
                $utmValue = 'Engagyo';
            }

            $existing = DomainUtmCode::withoutGlobalScopes()->where('user_id', $user->id)
                ->where('domain_name', $domainName)
                ->where('utm_key', $utmKey)
                ->first();

            if ($existing) {
                $existing->update(['utm_value' => $utmValue]);
            } else {
                DomainUtmCode::withoutGlobalScopes()->create([
                    'user_id' => $user->id,
                    'domain_name' => $domainName,
                    'utm_key' => $utmKey,
                    'utm_value' => $utmValue,
                ]);
            }
        }

        return redirect()->route('panel.url-tracking')->with('success', __('URL Tracking saved. Your domain ":domain" is now configured.', ['domain' => $domainName]));
    }

    private function normalizeDomainName(string $domainName): string
    {
        $domainName = preg_replace('#^https?://#', '', $domainName);
        $domainName = strtolower($domainName);
        if (str_starts_with($domainName, 'www.')) {
            $domainName = substr($domainName, 4);
        }
        $domainName = rtrim($domainName, '/');
        $parts = explode('/', $domainName);
        return $parts[0];
    }

    public function previewLink(Request $request)
    {
        $user = User::with("pages.facebook", "boards.pinterest")->findOrFail(Auth::guard('user')->id());
        $accounts = $user->getAccounts();
        $check = $accounts->where("schedule_status", "active")->where("type", "!=", "pinterest")->first();
        $pinterest_active = $check ? false : true;
        $link = $request->link;
        $link = $request->link;
        if (!empty($link)) {
            $max_tries = 3;
            $retry = 1;
            $service = new HtmlParseService($pinterest_active);
            while ($max_tries >= $retry) {
                $get_info = $service->get_info($link, 1);
                if ($get_info["status"] && !empty($get_info["title"]) && !empty($get_info["image"])) {
                    $response = array(
                        "success" => true,
                        "title" => isset($get_info["title"]) ? $get_info["title"] : "",
                        "image" => isset($get_info["image"]) ? $get_info["image"] : "",
                        "link" => $link,
                    );
                    break;
                } else {
                    $response = array(
                        "success" => false,
                        "message" => isset($get_info["message"]) ? $get_info["message"] : "Something went wrong!"
                    );
                    $retry++;
                    sleep(5);
                }
            }
        } else {
            $response = array(
                "success" => false,
                "message" => "Please enter a valid Link!",
            );
        }
        return response()->json($response);
    }
}
