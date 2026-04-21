<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class ThreadsPublishTestController extends Controller
{
    public function index()
    {
        $user = User::with('threads')->find(Auth::guard('user')->id());
        $threadsAccounts = $user ? $user->threads->sortBy('username')->values() : collect();

        return view('user.threads-test.index', compact('threadsAccounts'));
    }

    public function publish(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'thread_id' => ['required', 'integer'],
            'post_type' => ['required', 'in:text,image,video,carousel'],
            'caption' => ['nullable', 'string', 'max:500'],
            'files' => ['nullable', 'array'],
            'files.*' => ['file', 'max:102400'],
        ]);

        $steps = [];
        $addStep = function (string $title, string $status, array $meta = []) use (&$steps): void {
            $steps[] = [
                'title' => $title,
                'status' => $status,
                'meta' => $meta,
            ];
        };

        $userId = (int) Auth::guard('user')->id();
        $thread = Thread::query()
            ->where('id', (int) $validated['thread_id'])
            ->where('user_id', $userId)
            ->first();

        if (! $thread) {
            $addStep('Resolve Threads account', 'error', ['message' => 'Threads account not found for this user.']);

            return back()->withInput()->with('threads_test_result', ['success' => false, 'steps' => $steps]);
        }

        $accessToken = (string) ($thread->access_token ?? '');
        $threadsUserId = (string) ($thread->threads_id ?? '');
        if ($accessToken === '' || $threadsUserId === '') {
            $addStep('Validate Threads credentials', 'error', ['message' => 'Missing access token or Threads user id. Reconnect Threads account.']);

            return back()->withInput()->with('threads_test_result', ['success' => false, 'steps' => $steps]);
        }
        $addStep('Resolve Threads account', 'ok', ['thread_id' => $thread->id, 'username' => $thread->username]);

        $caption = trim((string) ($validated['caption'] ?? ''));
        $postType = (string) $validated['post_type'];
        $uploadedFiles = $request->file('files', []);

        if (! is_array($uploadedFiles)) {
            $uploadedFiles = [$uploadedFiles];
        }
        $uploadedFiles = array_values(array_filter($uploadedFiles));

        $makePublicUrl = function ($file) use ($addStep): ?string {
            $dir = public_path('uploads/threads-test');
            if (! is_dir($dir) && ! @mkdir($dir, 0775, true) && ! is_dir($dir)) {
                $addStep('Store upload', 'error', ['message' => 'Failed to create upload directory.']);

                return null;
            }

            $name = uniqid('threads_test_', true).'_'.$file->getClientOriginalName();
            $file->move($dir, $name);

            return asset('uploads/threads-test/'.$name);
        };

        $createContainer = function (array $payload) use ($threadsUserId, $addStep): ?string {
            $resp = Http::asForm()
                ->acceptJson()
                ->timeout(120)
                ->post("https://graph.threads.net/v1.0/{$threadsUserId}/threads", $payload);

            if (! $resp->successful()) {
                $addStep('Create media container', 'error', [
                    'payload' => $payload,
                    'response' => $resp->json() ?: $resp->body(),
                ]);

                return null;
            }

            $id = (string) ($resp->json('id') ?? '');
            if ($id === '') {
                $addStep('Create media container', 'error', ['response' => $resp->json() ?: $resp->body(), 'message' => 'Container id missing']);

                return null;
            }

            $addStep('Create media container', 'ok', ['container_id' => $id]);

            return $id;
        };

        $publishContainer = function (string $creationId) use ($threadsUserId, $accessToken, $addStep): ?string {
            $resp = Http::asForm()
                ->acceptJson()
                ->timeout(120)
                ->post("https://graph.threads.net/v1.0/{$threadsUserId}/threads_publish", [
                    'creation_id' => $creationId,
                    'access_token' => $accessToken,
                ]);

            if (! $resp->successful()) {
                $addStep('Publish container', 'error', ['creation_id' => $creationId, 'response' => $resp->json() ?: $resp->body()]);

                return null;
            }

            $postId = (string) ($resp->json('id') ?? '');
            if ($postId === '') {
                $addStep('Publish container', 'error', ['creation_id' => $creationId, 'response' => $resp->json() ?: $resp->body(), 'message' => 'Post id missing']);

                return null;
            }

            $addStep('Publish container', 'ok', ['post_id' => $postId]);

            return $postId;
        };

        $creationId = null;

        if ($postType === 'text') {
            $addStep('Build payload', 'ok', ['post_type' => 'text']);
            $creationId = $createContainer([
                'access_token' => $accessToken,
                'media_type' => 'TEXT',
                'text' => $caption,
            ]);
        } elseif ($postType === 'image' || $postType === 'video') {
            if (count($uploadedFiles) < 1) {
                $addStep('Validate files', 'error', ['message' => ucfirst($postType).' post requires at least one file.']);

                return back()->withInput()->with('threads_test_result', ['success' => false, 'steps' => $steps]);
            }
            $url = $makePublicUrl($uploadedFiles[0]);
            if ($url === null) {
                return back()->withInput()->with('threads_test_result', ['success' => false, 'steps' => $steps]);
            }
            $addStep('Store upload', 'ok', ['file_url' => $url]);

            $creationId = $createContainer([
                'access_token' => $accessToken,
                'media_type' => strtoupper($postType),
                'text' => $caption,
                $postType === 'image' ? 'image_url' : 'video_url' => $url,
            ]);
        } else {
            if (count($uploadedFiles) < 2) {
                $addStep('Validate files', 'error', ['message' => 'Carousel post requires at least 2 files.']);

                return back()->withInput()->with('threads_test_result', ['success' => false, 'steps' => $steps]);
            }
            if (count($uploadedFiles) > 20) {
                $addStep('Validate files', 'error', ['message' => 'Carousel supports up to 20 files.']);

                return back()->withInput()->with('threads_test_result', ['success' => false, 'steps' => $steps]);
            }

            $children = [];
            foreach ($uploadedFiles as $idx => $file) {
                $mime = (string) $file->getMimeType();
                $isVideo = str_starts_with($mime, 'video/');
                $url = $makePublicUrl($file);
                if ($url === null) {
                    return back()->withInput()->with('threads_test_result', ['success' => false, 'steps' => $steps]);
                }
                $addStep('Store upload #'.($idx + 1), 'ok', ['file_url' => $url, 'mime' => $mime]);

                $childId = $createContainer([
                    'access_token' => $accessToken,
                    'media_type' => $isVideo ? 'VIDEO' : 'IMAGE',
                    $isVideo ? 'video_url' : 'image_url' => $url,
                    'is_carousel_item' => 'true',
                ]);
                if ($childId === null) {
                    return back()->withInput()->with('threads_test_result', ['success' => false, 'steps' => $steps]);
                }
                $children[] = $childId;
            }

            $addStep('Build carousel payload', 'ok', ['children_count' => count($children)]);
            $creationId = $createContainer([
                'access_token' => $accessToken,
                'media_type' => 'CAROUSEL',
                'children' => implode(',', $children),
                'text' => $caption,
            ]);
        }

        if ($creationId === null) {
            return back()->withInput()->with('threads_test_result', ['success' => false, 'steps' => $steps]);
        }

        $postId = $publishContainer($creationId);
        if ($postId === null) {
            return back()->withInput()->with('threads_test_result', ['success' => false, 'steps' => $steps]);
        }

        $addStep('Done', 'ok', ['message' => 'Threads post published successfully.', 'post_id' => $postId]);

        return back()->with('threads_test_result', ['success' => true, 'steps' => $steps, 'post_id' => $postId]);
    }
}
