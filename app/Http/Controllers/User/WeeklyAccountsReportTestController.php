<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Services\WeeklyAccountsReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WeeklyAccountsReportTestController extends Controller
{
    public function send(Request $request, WeeklyAccountsReportService $reportService): JsonResponse
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

        $result = $reportService->sendTestToUser($user);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    private function testRouteEnabled(): bool
    {
        if (config('mail_branding.weekly_accounts_report_test_route', false)) {
            return true;
        }

        return app()->environment('local');
    }
}
