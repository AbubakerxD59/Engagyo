<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use App\Models\ApiKey;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends BaseController
{
    /**
     * Validate API credentials and return user info.
     * This is the authentication test endpoint.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function test(Request $request)
    {
        $user = $request->user();
        
        return $this->successResponse([
            'authenticated' => true,
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'name' => $user->full_name,
            ],
            'api_key' => [
                'id' => $request->api_key_id,
                'last_used_at' => now()->toIso8601String(),
            ]
        ], 'Authentication successful');
    }

    /**
     * Login with email and password to get API keys.
     * This endpoint does not require API key authentication.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->getAttributes()['password'])) {
            return $this->errorResponse('Invalid email or password', 401);
        }

        if ($user->getAttributes()['status'] != 1) {
            return $this->errorResponse('Your account is not active', 403);
        }

        // Get user's API keys
        $apiKeys = $user->apiKeys()->active()->get(['id', 'name', 'key', 'last_used_at', 'created_at']);

        return $this->successResponse([
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'name' => $user->full_name,
            ],
            'api_keys' => $apiKeys,
        ], 'Login successful');
    }

    /**
     * Create a new API key for the authenticated user.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createApiKey(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        $user = $request->user();

        // Check if user already has too many API keys (limit to 10)
        if ($user->apiKeys()->count() >= 10) {
            return $this->errorResponse('Maximum number of API keys reached (10)', 400);
        }

        $key = ApiKey::generateKey();
        $apiKey = $user->apiKeys()->create([
            'name' => $request->name,
            'key' => $key,
            'secret' => ApiKey::generateSecret(),
        ]);

        return $this->successResponse([
            'api_key' => [
                'id' => $apiKey->id,
                'name' => $apiKey->name,
                'key' => $key, // Only shown once during creation
                'created_at' => $apiKey->created_at->toIso8601String(),
            ]
        ], 'API key created successfully. Please save this key securely, it will not be shown again.');
    }

    /**
     * Refresh an existing API key.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function refreshApiKey(Request $request, $id)
    {
        $user = $request->user();
        $apiKey = $user->apiKeys()->find($id);

        if (!$apiKey) {
            return $this->errorResponse('API key not found', 404);
        }

        $newKey = $apiKey->refresh();

        return $this->successResponse([
            'api_key' => [
                'id' => $apiKey->id,
                'name' => $apiKey->name,
                'key' => $newKey, // Only shown once during refresh
                'created_at' => $apiKey->created_at->toIso8601String(),
                'updated_at' => $apiKey->updated_at->toIso8601String(),
            ]
        ], 'API key refreshed successfully. Please save this new key securely.');
    }

    /**
     * Delete an API key.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteApiKey(Request $request, $id)
    {
        $user = $request->user();
        $apiKey = $user->apiKeys()->find($id);

        if (!$apiKey) {
            return $this->errorResponse('API key not found', 404);
        }

        // Prevent deleting the API key being used for this request
        if ($apiKey->id == $request->api_key_id) {
            return $this->errorResponse('Cannot delete the API key currently in use', 400);
        }

        $apiKey->delete();

        return $this->successResponse([], 'API key deleted successfully');
    }

    /**
     * List all API keys for the authenticated user.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function listApiKeys(Request $request)
    {
        $user = $request->user();
        $apiKeys = $user->apiKeys()->get(['id', 'name', 'last_used_at', 'is_active', 'created_at']);

        return $this->successResponse([
            'api_keys' => $apiKeys->map(function ($key) use ($request) {
                return [
                    'id' => $key->id,
                    'name' => $key->name,
                    'is_current' => $key->id == $request->api_key_id,
                    'is_active' => $key->is_active,
                    'last_used_at' => $key->last_used_at?->toIso8601String(),
                    'created_at' => $key->created_at->toIso8601String(),
                ];
            }),
            'total' => $apiKeys->count(),
        ]);
    }

    /**
     * Toggle API key active status.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleApiKey(Request $request, $id)
    {
        $user = $request->user();
        $apiKey = $user->apiKeys()->find($id);

        if (!$apiKey) {
            return $this->errorResponse('API key not found', 404);
        }

        // Prevent deactivating the API key being used for this request
        if ($apiKey->id == $request->api_key_id && $apiKey->is_active) {
            return $this->errorResponse('Cannot deactivate the API key currently in use', 400);
        }

        $apiKey->update(['is_active' => !$apiKey->is_active]);

        return $this->successResponse([
            'api_key' => [
                'id' => $apiKey->id,
                'name' => $apiKey->name,
                'is_active' => $apiKey->is_active,
            ]
        ], $apiKey->is_active ? 'API key activated' : 'API key deactivated');
    }
}

