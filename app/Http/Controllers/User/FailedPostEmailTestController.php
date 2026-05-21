<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Services\FailedPostEmailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FailedPostEmailTestController extends Controller
{
    public function send(Request $request, FailedPostEmailService $failedPostEmailService): JsonResponse
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

        $post = Post::where('user_id', $user->id)
            ->where('status', -1)
            ->orderByDesc('id')
            ->first();

        $usingSampleData = false;

        if (! $post) {
            $post = Post::where('user_id', $user->id)->orderByDesc('id')->first();
            if (! $post) {
                return response()->json([
                    'success' => false,
                    'message' => 'No posts found for your account. Create a post first, then try again.',
                ], 404);
            }

            $post->status = -1;
            $post->response = json_encode([
                'success' => false,
                'message' => 'Test failure: this is a sample failed-post email (not a real publish failure).',
            ]);
            $usingSampleData = true;
        }

        $result = $failedPostEmailService->sendTestToUser($user, $post);

        return response()->json(array_merge($result, [
            'post_id' => $post->id,
            'using_sample_failure_data' => $usingSampleData,
        ]), $result['success'] ? 200 : 422);
    }

    private function testRouteEnabled(): bool
    {
        if (config('mail_branding.failed_post_email_test_route', false)) {
            return true;
        }

        return app()->environment('local');
    }
}
