<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiKey
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $this->getApiKeyFromRequest($request);

        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'message' => 'API key is required. Please provide a valid API key in the Authorization header.',
                'error' => 'unauthorized'
            ], 401);
        }

        $apiKeyModel = ApiKey::findByKey($apiKey);

        if (!$apiKeyModel) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid API key. Please check your API key and try again.',
                'error' => 'invalid_api_key'
            ], 401);
        }

        // Update last used timestamp
        $apiKeyModel->markAsUsed();

        // Attach the user and api key to the request
        $request->merge(['api_key_id' => $apiKeyModel->id]);
        $request->setUserResolver(function () use ($apiKeyModel) {
            return $apiKeyModel->user;
        });

        return $next($request);
    }

    /**
     * Get API key from the request.
     *
     * @param Request $request
     * @return string|null
     */
    protected function getApiKeyFromRequest(Request $request): ?string
    {
        // Try Authorization header first (Bearer token format)
        $authHeader = $request->header('Authorization');
        if ($authHeader) {
            // Support both "Bearer {key}" and just "{key}"
            if (str_starts_with($authHeader, 'Bearer ')) {
                return substr($authHeader, 7);
            }
            return $authHeader;
        }

        // Try X-API-Key header
        $xApiKey = $request->header('X-API-Key');
        if ($xApiKey) {
            return $xApiKey;
        }

        // Try query parameter as fallback
        return $request->query('api_key');
    }
}
