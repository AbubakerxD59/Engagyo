<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\BaseController;
use App\Models\ApiKey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ApiKeysController extends BaseController
{
    /**
     * Display API keys management page.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $user = Auth::guard('user')->user();
        $apiKeys = $user->apiKeys()->orderBy('created_at', 'desc')->get();
        
        return view('user.api-keys.index', compact('apiKeys'));
    }

    /**
     * Create a new API key.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        $user = Auth::guard('user')->user();

        // Check if user already has too many API keys (limit to 10)
        if ($user->apiKeys()->count() >= 10) {
            return $this->errorResponse('Maximum number of API keys reached (10)', 400);
        }

        // Check for duplicate names
        if ($user->apiKeys()->where('name', $request->name)->exists()) {
            return $this->errorResponse('An API key with this name already exists', 400);
        }

        $key = ApiKey::generateKey();
        $apiKey = $user->apiKeys()->create([
            'name' => $request->name,
            'key' => $key,
            'secret' => ApiKey::generateSecret(),
        ]);

        return $this->successResponse([
            'id' => $apiKey->id,
            'name' => $apiKey->name,
            'key' => $key,
            'created_at' => $apiKey->created_at->format('M d, Y H:i'),
        ], 'API key created successfully');
    }

    /**
     * Refresh an API key.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh(Request $request, $id)
    {
        $user = Auth::guard('user')->user();
        $apiKey = $user->apiKeys()->find($id);

        if (!$apiKey) {
            return $this->errorResponse('API key not found', 404);
        }

        $newKey = $apiKey->refresh();

        return $this->successResponse([
            'id' => $apiKey->id,
            'key' => $newKey,
        ], 'API key refreshed successfully');
    }

    /**
     * Toggle API key status.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggle(Request $request, $id)
    {
        $user = Auth::guard('user')->user();
        $apiKey = $user->apiKeys()->find($id);

        if (!$apiKey) {
            return $this->errorResponse('API key not found', 404);
        }

        $apiKey->update(['is_active' => !$apiKey->is_active]);

        return $this->successResponse([
            'id' => $apiKey->id,
            'is_active' => $apiKey->is_active,
        ], $apiKey->is_active ? 'API key activated' : 'API key deactivated');
    }

    /**
     * Delete an API key.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $id)
    {
        $user = Auth::guard('user')->user();
        $apiKey = $user->apiKeys()->find($id);

        if (!$apiKey) {
            return $this->errorResponse('API key not found', 404);
        }

        $apiKey->delete();

        return $this->successResponse([], 'API key deleted successfully');
    }

    /**
     * Get API keys list for DataTable.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function dataTable(Request $request)
    {
        $user = Auth::guard('user')->user();
        $apiKeys = $user->apiKeys()->orderBy('created_at', 'desc')->get();

        return $this->successResponse([
            'data' => $apiKeys->map(function ($key) {
                return [
                    'id' => $key->id,
                    'name' => $key->name,
                    'key_preview' => substr($key->key, 0, 20) . '...',
                    'is_active' => $key->is_active,
                    'last_used_at' => $key->last_used_at ? $key->last_used_at->format('M d, Y H:i') : 'Never',
                    'created_at' => $key->created_at->format('M d, Y H:i'),
                ];
            }),
        ]);
    }
}

