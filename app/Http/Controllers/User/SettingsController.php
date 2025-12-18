<?php

namespace App\Http\Controllers\User;

use App\Models\User;
use App\Models\Timezone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\BaseController;
use Illuminate\Support\Facades\Validator;

class SettingsController extends BaseController
{
    /**
     * Display the settings page.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $user = User::with("userPackages.package", "userPackages.package.features")->findOrFail(auth()->id());
        $timezones = Timezone::orderBy('offset')->orderBy('name')->get();

        // Get active package
        $activePackage = $user->activeUserPackage;
        $package = $activePackage ? $activePackage->package : null;

        // Get package status
        $packageStatus = 'No Package';
        $expiryDate = null;
        if ($activePackage && $package) {
            if ($package->is_lifetime) {
                $packageStatus = 'Lifetime';
            } elseif ($activePackage->expires_at) {
                $expiryDate = $activePackage->expires_at;
                if ($expiryDate->isPast()) {
                    $packageStatus = 'Expired';
                } elseif ($expiryDate->isToday() || $expiryDate->isFuture()) {
                    $packageStatus = 'Active';
                }
            } else {
                $packageStatus = 'Active';
            }
        }

        // Get features with usage
        $featuresWithUsage = [];
        if ($activePackage && $package) {
            $packageFeatures = $package->features()
                ->wherePivot('is_enabled', true)
                ->where('is_active', true)
                ->get();

            foreach ($packageFeatures as $feature) {
                $usage = $user->getFeatureUsage($feature->key);
                $limit = $feature->pivot->limit_value;
                $isUnlimited = $feature->pivot->is_unlimited ?? false;

                $featuresWithUsage[] = [
                    'id' => $feature->id,
                    'key' => $feature->key,
                    'name' => $feature->name,
                    'description' => $feature->description ?? '',
                    'usage' => $usage,
                    'limit' => $isUnlimited ? null : $limit,
                    'is_unlimited' => $isUnlimited,
                    'remaining' => $isUnlimited ? null : ($limit !== null ? max(0, $limit - $usage) : null),
                ];
            }
        }

        return view('user.settings.index', compact('user', 'timezones', 'package', 'packageStatus', 'expiryDate', 'featuresWithUsage'));
    }

    /**
     * Update the user's profile.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request)
    {
        $user = auth()->user();

        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:150',
            'last_name' => 'required|string|max:150',
            'phone_number' => 'nullable|string|max:20',
            'city' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:500',
            'timezone_id' => 'nullable|exists:timezones,id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        $user->update($request->only([
            'first_name',
            'last_name',
            'phone_number',
            'city',
            'country',
            'address',
            'timezone_id',
        ]));

        return $this->successResponse([
            'user' => [
                'full_name' => $user->full_name,
            ]
        ], 'Profile updated successfully');
    }

    /**
     * Update the user's profile picture.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateProfilePic(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'profile_pic' => 'required|image|mimes:jpeg,jpg,png,gif,webp|max:5120', // 5MB max
        ], [
            'profile_pic.required' => 'Please select an image to upload.',
            'profile_pic.image' => 'The file must be an image.',
            'profile_pic.mimes' => 'The image must be a jpeg, jpg, png, gif, or webp file.',
            'profile_pic.max' => 'The image size must not exceed 5MB.',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        $user = auth()->user();

        // Delete old profile pic if exists
        if ($user->profile_pic && file_exists(public_path($user->profile_pic))) {
            unlink(public_path($user->profile_pic));
        }

        // Upload new profile pic
        $file = $request->file('profile_pic');
        $filename = 'profile_' . $user->id . '_' . time() . '.' . $file->getClientOriginalExtension();
        $path = 'uploads/profiles/';

        // Ensure directory exists
        if (!file_exists(public_path($path))) {
            mkdir(public_path($path), 0755, true);
        }

        $file->move(public_path($path), $filename);

        $user->update([
            'profile_pic' => $path . $filename
        ]);

        return $this->successResponse([
            'profile_pic' => asset($user->profile_pic)
        ], 'Profile picture updated successfully');
    }

    /**
     * Remove the user's profile picture.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeProfilePic()
    {
        $user = auth()->user();

        // Delete profile pic if exists
        if ($user->profile_pic && file_exists(public_path($user->profile_pic))) {
            unlink(public_path($user->profile_pic));
        }

        $user->update(['profile_pic' => null]);

        return $this->successResponse([], 'Profile picture removed successfully');
    }

    /**
     * Update the user's password.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updatePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'password.confirmed' => 'The new password confirmation does not match.',
            'password.min' => 'The new password must be at least 8 characters.',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        $user = auth()->user();

        // Check current password
        if (!password_verify($request->current_password, $user->getAttributes()['password'])) {
            return $this->errorResponse('Current password is incorrect', 422);
        }

        $user->update(['password' => $request->password]);

        return $this->successResponse([], 'Password updated successfully');
    }
}
