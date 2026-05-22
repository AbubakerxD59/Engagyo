<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Services\PackageExpirationEmailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PackageExpirationEmailTestController extends Controller
{
    public function send(Request $request, PackageExpirationEmailService $service): JsonResponse
    {
        if (! $this->testRouteEnabled()) {
            abort(404);
        }

        $user = $request->user('user');
        if (! $user || empty($user->email)) {
            return response()->json([
                'success' => false,
                'message' => 'Authenticated user has no email address.',
            ], 422);
        }

        $type = $request->query('type', 'warning');
        if (! in_array($type, ['warning', 'expired'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid type. Use warning or expired.',
            ], 422);
        }

        $result = $service->sendTestToUser($user, $type);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    private function testRouteEnabled(): bool
    {
        if (config('mail_branding.package_expiration_test_route', false)) {
            return true;
        }

        return app()->environment('local');
    }
}
