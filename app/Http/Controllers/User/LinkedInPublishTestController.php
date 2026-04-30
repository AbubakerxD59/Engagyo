<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Linkedin;
use App\Models\Post;
use App\Models\User;
use App\Services\LinkedInPublishService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
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
            'image_file' => ['nullable', 'file', 'image', 'max:15360'],
            'video_url' => ['nullable', 'url'],
            'video_file' => ['nullable', 'file', 'mimetypes:video/mp4,video/quicktime,video/x-msvideo,video/x-matroska,video/webm', 'max:102400'],
            'carousel_urls' => ['nullable', 'string'],
            'carousel_files' => ['nullable', 'array'],
            'carousel_files.*' => ['file', 'image', 'max:15360'],
            'document_url' => ['nullable', 'url'],
            'document_name' => ['nullable', 'string', 'max:255'],
            'document_file' => ['nullable', 'file', 'mimes:pdf,doc,docx,ppt,pptx', 'max:51200'],
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
            $imagePath = null;
            if ($request->hasFile('image_file')) {
                $imagePath = saveImage($request->file('image_file'));
            } elseif (! empty($validated['image_url'])) {
                $imagePath = $validated['image_url'];
            }

            if (empty($imagePath)) {
                $addStep('build_payload', 'error', ['message' => 'Image URL is required for photo type.']);

                return back()->withInput()
                    ->with('linkedin_publish_test_steps', $steps)
                    ->with('linkedin_publish_test_result', ['success' => false]);
            }
            $post->image = $imagePath;
        } elseif ($type === 'video') {
            $videoPath = null;
            if ($request->hasFile('video_file')) {
                $videoPath = saveToS3($request->file('video_file'));
            } elseif (! empty($validated['video_url'])) {
                $videoPath = $validated['video_url'];
            }

            if (empty($videoPath)) {
                $addStep('build_payload', 'error', ['message' => 'Video URL is required for video type.']);

                return back()->withInput()
                    ->with('linkedin_publish_test_steps', $steps)
                    ->with('linkedin_publish_test_result', ['success' => false]);
            }
            $post->video = $videoPath;
        } elseif ($type === 'carousel') {
            $carouselItems = [];
            $rawCarousel = trim((string) ($validated['carousel_urls'] ?? ''));
            $urls = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $rawCarousel ?: ''))));
            foreach ($urls as $url) {
                $carouselItems[] = $url;
            }
            $files = $request->file('carousel_files', []);
            if (! is_array($files)) {
                $files = [$files];
            }
            foreach (array_filter($files) as $file) {
                if ($file instanceof UploadedFile && $file->isValid()) {
                    $carouselItems[] = saveImage($file);
                }
            }

            if (count($carouselItems) < 2) {
                $addStep('build_payload', 'error', ['message' => 'Carousel type requires at least 2 image URLs (one per line).']);

                return back()->withInput()
                    ->with('linkedin_publish_test_steps', $steps)
                    ->with('linkedin_publish_test_result', ['success' => false]);
            }
            $post->metadata = json_encode(['linkedin_carousel' => $carouselItems]);
        } elseif ($type === 'document') {
            $documentPath = null;
            if ($request->hasFile('document_file')) {
                $documentPath = saveToS3($request->file('document_file'));
            } elseif (! empty($validated['document_url'])) {
                $documentPath = $validated['document_url'];
            }

            if (empty($documentPath)) {
                $addStep('build_payload', 'error', ['message' => 'Document URL is required for document type.']);

                return back()->withInput()
                    ->with('linkedin_publish_test_steps', $steps)
                    ->with('linkedin_publish_test_result', ['success' => false]);
            }
            $post->metadata = json_encode([
                'linkedin_document' => [
                    'path' => $documentPath,
                    'name' => $validated['document_name'] ?: ($request->file('document_file')?->getClientOriginalName() ?: 'document'),
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

