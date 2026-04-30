<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Linkedin;
use App\Models\Post;
use App\Models\User;
use App\Services\LinkedInPublishService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class LinkedInPublishTestController extends Controller
{
    public function index()
    {
        /** @var User $user */
        $user = Auth::guard('user')->user();
        $ownerId = (int) ($user->getEffectiveUser()?->id ?? $user->id);

        $accounts = Linkedin::query()
            ->where('user_id', $ownerId)
            ->orderBy('username')
            ->get();

        return view('user.linkedin-publish-test.index', [
            'accounts' => $accounts,
            'steps' => session('linkedin_publish_test_steps', []),
            'result' => session('linkedin_publish_test_result'),
        ]);
    }

    public function publish(Request $request, LinkedInPublishService $linkedInPublishService)
    {
        /** @var User $user */
        $user = Auth::guard('user')->user();
        $ownerId = (int) ($user->getEffectiveUser()?->id ?? $user->id);

        $validated = $request->validate([
            'linkedin_account_id' => [
                'required',
                'integer',
                Rule::exists('linkedins', 'id')->where('user_id', $ownerId),
            ],
            'type' => ['required', Rule::in(['content_only', 'photo', 'video', 'carousel', 'document'])],
            'title' => ['nullable', 'string', 'max:3000'],
            'comment' => ['nullable', 'string', 'max:3000'],
            'image_url' => ['nullable', 'url'],
            'video_url' => ['nullable', 'url'],
            'carousel_urls' => ['nullable', 'string'],
            'document_url' => ['nullable', 'url'],
            'document_name' => ['nullable', 'string', 'max:255'],
        ]);

        $steps = [];
        $addStep = function (string $step, string $status, array $payload = []) use (&$steps): void {
            $steps[] = [
                'step' => $step,
                'status' => $status,
                'payload' => $payload,
            ];
        };

        $account = Linkedin::query()
            ->where('id', (int) $validated['linkedin_account_id'])
            ->where('user_id', $ownerId)
            ->first();

        if (! $account) {
            $addStep('resolve_account', 'error', ['message' => 'LinkedIn account not found for this user.']);

            return back()->withInput()
                ->with('linkedin_publish_test_steps', $steps)
                ->with('linkedin_publish_test_result', ['success' => false]);
        }

        if (! $account->validToken()) {
            $addStep('validate_token', 'error', ['message' => 'LinkedIn token expired. Reconnect LinkedIn account.']);

            return back()->withInput()
                ->with('linkedin_publish_test_steps', $steps)
                ->with('linkedin_publish_test_result', ['success' => false]);
        }

        $addStep('resolve_account', 'ok', [
            'account_id' => $account->id,
            'linkedin_id' => $account->linkedin_id,
            'username' => $account->username,
        ]);

        $type = (string) $validated['type'];
        $post = new Post();
        $post->social_type = 'linkedin';
        $post->type = $type;
        $post->title = (string) ($validated['title'] ?? '');
        $post->comment = (string) ($validated['comment'] ?? '');
        $post->image = null;
        $post->video = null;
        $post->metadata = null;

        if ($type === 'photo') {
            if (empty($validated['image_url'])) {
                $addStep('build_payload', 'error', ['message' => 'Image URL is required for photo type.']);

                return back()->withInput()
                    ->with('linkedin_publish_test_steps', $steps)
                    ->with('linkedin_publish_test_result', ['success' => false]);
            }
            $post->image = $validated['image_url'];
        } elseif ($type === 'video') {
            if (empty($validated['video_url'])) {
                $addStep('build_payload', 'error', ['message' => 'Video URL is required for video type.']);

                return back()->withInput()
                    ->with('linkedin_publish_test_steps', $steps)
                    ->with('linkedin_publish_test_result', ['success' => false]);
            }
            $post->video = $validated['video_url'];
        } elseif ($type === 'carousel') {
            $rawCarousel = trim((string) ($validated['carousel_urls'] ?? ''));
            $urls = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $rawCarousel ?: ''))));
            if (count($urls) < 2) {
                $addStep('build_payload', 'error', ['message' => 'Carousel type requires at least 2 image URLs (one per line).']);

                return back()->withInput()
                    ->with('linkedin_publish_test_steps', $steps)
                    ->with('linkedin_publish_test_result', ['success' => false]);
            }
            $post->metadata = json_encode(['linkedin_carousel' => $urls]);
        } elseif ($type === 'document') {
            if (empty($validated['document_url'])) {
                $addStep('build_payload', 'error', ['message' => 'Document URL is required for document type.']);

                return back()->withInput()
                    ->with('linkedin_publish_test_steps', $steps)
                    ->with('linkedin_publish_test_result', ['success' => false]);
            }
            $post->metadata = json_encode([
                'linkedin_document' => [
                    'path' => $validated['document_url'],
                    'name' => $validated['document_name'] ?: 'document',
                ],
            ]);
        }

        $addStep('build_payload', 'ok', [
            'type' => $post->type,
            'title' => $post->title,
            'comment' => $post->comment,
            'image' => $post->image,
            'video' => $post->video,
            'metadata' => $post->metadata ? json_decode((string) $post->metadata, true) : null,
        ]);

        $result = $linkedInPublishService->publish($post, $account);

        $addStep('publish', ($result['success'] ?? false) ? 'ok' : 'error', $result);

        return back()->withInput()
            ->with('linkedin_publish_test_steps', $steps)
            ->with('linkedin_publish_test_result', $result);
    }
}

