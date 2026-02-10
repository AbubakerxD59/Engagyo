<?php

namespace App\Http\Controllers\User;

use Exception;
use App\Models\DomainUtmCode;
use App\Models\Feature;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Services\FeatureUsageService;

class UrlTrackingController extends Controller
{
    protected $featureUsageService;

    public function __construct(FeatureUsageService $featureUsageService)
    {
        $this->featureUsageService = $featureUsageService;
    }
    /**
     * Display a listing of URL tracking configurations
     */
    public function index()
    {
        $user = Auth::guard('user')->user();
        $utmCodes = DomainUtmCode::where('user_id', $user->id)
            ->orderBy('domain_name')
            ->orderBy('utm_key')
            ->get()
            ->groupBy('domain_name');

        return view('user.url-tracking.index', compact('utmCodes'));
    }

    /**
     * Store a newly created UTM code
     */
    public function store(Request $request)
    {
        try {
            $user = Auth::guard('user')->user();

            $request->validate([
                'domain_name' => 'required|string|max:255',
                'utm_codes' => 'required|array',
                'utm_codes.*.key' => 'required|string|max:255',
                'utm_codes.*.value' => 'nullable|string|max:255',
            ]);

            // Normalize domain name
            $domainName = $this->normalizeDomainName($request->domain_name);

            // Ensure utm_source is always present with value "Engagyo"
            $utmCodes = $request->utm_codes;
            $hasUtmSource = false;
            $utmSourceIndex = null;

            // Check if utm_source exists in the array
            foreach ($utmCodes as $index => $utmCodeData) {
                if (isset($utmCodeData['key']) && $utmCodeData['key'] === 'utm_source') {
                    $hasUtmSource = true;
                    $utmSourceIndex = $index;
                    break;
                }
            }

            // If utm_source doesn't exist, add it; if it exists, override its value
            if (!$hasUtmSource) {
                $utmCodes[] = [
                    'key' => 'utm_source',
                    'value' => 'Engagyo'
                ];
            } else {
                $utmCodes[$utmSourceIndex]['value'] = 'Engagyo';
            }

            // Check if this is a new domain (no existing UTM codes for this domain)
            $isNewDomain = !DomainUtmCode::where('user_id', $user->id)
                ->where('domain_name', $domainName)
                ->exists();

            // If it's a new domain, check and increment feature usage
            if ($isNewDomain) {
                $result = $this->featureUsageService->checkAndIncrement($user, Feature::$features_list[6], 1);

                if (!$result['allowed']) {
                    return response()->json([
                        "success" => false,
                        "message" => $result['message'],
                        "usage" => $result['usage'] ?? 0,
                        "limit" => $result['limit'] ?? null,
                        "remaining" => $result['remaining'] ?? null
                    ], 403);
                }
            }

            $created = [];
            $updated = [];
            $skipped = [];

            foreach ($utmCodes as $utmCodeData) {
                $utmKey = $utmCodeData['key'];
                $utmValue = $utmCodeData['value'] ?? '';

                // Always override utm_source value to "Engagyo" as a safety measure
                if ($utmKey === 'utm_source') {
                    $utmValue = 'Engagyo';
                }

                // Skip if value is empty (but never skip utm_source)
                if (empty(trim($utmValue)) && $utmKey !== 'utm_source') {
                    $skipped[] = $utmKey;
                    continue;
                }

                // Ensure utm_source always has a value
                if ($utmKey === 'utm_source' && empty(trim($utmValue))) {
                    $utmValue = 'Engagyo';
                }

                // Check if UTM code already exists
                $utmCode = DomainUtmCode::where('user_id', $user->id)
                    ->where('domain_name', $domainName)
                    ->where('utm_key', $utmKey)
                    ->first();

                if ($utmCode) {
                    // Update existing UTM code
                    $utmCode->update([
                        'utm_value' => $utmValue,
                    ]);
                    $updated[] = $utmCode;
                } else {
                    // Create new UTM code
                    $utmCode = DomainUtmCode::create([
                        'user_id' => $user->id,
                        'domain_name' => $domainName,
                        'utm_key' => $utmKey,
                        'utm_value' => $utmValue,
                    ]);
                    $created[] = $utmCode;
                }
            }

            // Build success message
            $messages = [];
            if (count($created) > 0) {
                $messages[] = count($created) . " UTM code(s) created";
            }
            if (count($updated) > 0) {
                $messages[] = count($updated) . " UTM code(s) updated";
            }
            if (count($skipped) > 0) {
                $messages[] = count($skipped) . " skipped (empty values)";
            }

            $message = !empty($messages)
                ? implode(", ", $messages) . " successfully!"
                : "No UTM codes were saved. Please provide at least one value.";

            return response()->json([
                "success" => true,
                "message" => $message,
                "data" => array_merge($created, $updated)
            ]);
        } catch (Exception $e) {
            return response()->json([
                "success" => false,
                "message" => $e->getMessage()
            ]);
        }
    }

    /**
     * Normalize domain name
     */
    private function normalizeDomainName($domainName)
    {
        // Remove protocol
        $domainName = preg_replace('#^https?://#', '', $domainName);
        $domainName = strtolower($domainName);
        // Remove www. prefix
        if (strpos($domainName, 'www.') === 0) {
            $domainName = substr($domainName, 4);
        }
        // Remove trailing slash
        $domainName = rtrim($domainName, '/');
        // Extract just the domain (remove path)
        $parts = explode('/', $domainName);
        return $parts[0];
    }

    /**
     * Get a specific UTM code
     */
    public function show($id)
    {
        try {
            $user = Auth::guard('user')->user();
            $utmCode = DomainUtmCode::where('user_id', $user->id)
                ->where('id', $id)
                ->firstOrFail();

            return response()->json([
                "success" => true,
                "data" => $utmCode
            ]);
        } catch (Exception $e) {
            return response()->json([
                "success" => false,
                "message" => "UTM code not found!"
            ]);
        }
    }

    /**
     * Get all UTM codes for a domain
     */
    public function getByDomain(Request $request)
    {
        try {
            $user = Auth::guard('user')->user();
            $domainName = $this->normalizeDomainName($request->domain_name);

            $utmCodes = DomainUtmCode::where('user_id', $user->id)
                ->where('domain_name', $domainName)
                ->get();

            return response()->json([
                "success" => true,
                "data" => $utmCodes,
                "domain_name" => $domainName
            ]);
        } catch (Exception $e) {
            return response()->json([
                "success" => false,
                "message" => "Failed to fetch UTM codes: " . $e->getMessage()
            ]);
        }
    }

    /**
     * Update the specified UTM code
     */
    public function update(Request $request, $id)
    {
        try {
            $user = Auth::guard('user')->user();

            $request->validate([
                'domain_name' => 'required|string|max:255',
                'utm_key' => 'required|string|max:255',
                'utm_value' => 'required|string|max:255',
            ]);

            $utmCode = DomainUtmCode::where('user_id', $user->id)
                ->where('id', $id)
                ->firstOrFail();

            // Normalize domain name
            $domainName = $this->normalizeDomainName($request->domain_name);

            // Check if another UTM code exists with same domain and key (excluding current)
            $existing = DomainUtmCode::where('user_id', $user->id)
                ->where('domain_name', $domainName)
                ->where('utm_key', $request->utm_key)
                ->where('id', '!=', $id)
                ->first();

            if ($existing) {
                return response()->json([
                    "success" => false,
                    "message" => "A UTM code with this key already exists for this domain!"
                ]);
            }

            // Always override utm_source value to "Engagyo"
            $utmValue = $request->utm_value;
            if ($request->utm_key === 'utm_source') {
                $utmValue = 'Engagyo';
            }

            $utmCode->update([
                'domain_name' => $domainName,
                'utm_key' => $request->utm_key,
                'utm_value' => $utmValue,
            ]);

            return response()->json([
                "success" => true,
                "message" => "UTM code updated successfully!",
                "data" => $utmCode
            ]);
        } catch (Exception $e) {
            return response()->json([
                "success" => false,
                "message" => $e->getMessage()
            ]);
        }
    }

    /**
     * Remove the specified UTM code
     */
    public function destroy($id)
    {
        try {
            $user = Auth::guard('user')->user();
            $utmCode = DomainUtmCode::where('user_id', $user->id)
                ->where('id', $id)
                ->firstOrFail();

            $domainName = $utmCode->domain_name;
            $utmCode->delete();

            // Check if this was the last UTM code for this domain
            $remainingCodesCount = DomainUtmCode::where('user_id', $user->id)
                ->where('domain_name', $domainName)
                ->count();

            // If no codes remain for this domain, decrement feature usage
            if ($remainingCodesCount === 0) {
                $user->decrementFeatureUsage(Feature::$features_list[6], 1);
            }

            return response()->json([
                "success" => true,
                "message" => "UTM code deleted successfully!"
            ]);
        } catch (Exception $e) {
            return response()->json([
                "success" => false,
                "message" => $e->getMessage()
            ]);
        }
    }

    /**
     * Delete all UTM codes for a domain
     */
    public function deleteAllForDomain(Request $request)
    {
        try {
            $user = Auth::guard('user')->user();
            $domainName = $request->get('domain_name');

            if (empty($domainName)) {
                return response()->json([
                    "success" => false,
                    "message" => "Domain name is required!"
                ]);
            }

            // Normalize domain name
            $normalizedDomain = $this->normalizeDomainName($domainName);

            $deletedCount = DomainUtmCode::where('user_id', $user->id)
                ->where('domain_name', $normalizedDomain)
                ->count();

            DomainUtmCode::where('user_id', $user->id)
                ->where('domain_name', $normalizedDomain)
                ->delete();

            // Decrement feature usage by 1 (one domain deleted, regardless of code count)
            if ($deletedCount > 0) {
                $user->decrementFeatureUsage(Feature::$features_list[6], 1);
            }

            return response()->json([
                "success" => true,
                "message" => "Deleted {$deletedCount} UTM code(s) successfully!"
            ]);
        } catch (Exception $e) {
            return response()->json([
                "success" => false,
                "message" => $e->getMessage()
            ]);
        }
    }
}
