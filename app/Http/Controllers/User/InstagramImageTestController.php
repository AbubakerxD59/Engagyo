<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\InstagramAccount;
use App\Models\Post;
use App\Models\User;
use App\Services\InstagramGraphService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * Dev / QA: publish a single feed photo to Instagram using the real publishing pipeline.
 * Gated by APP_DEBUG or INSTAGRAM_IMAGE_PUBLISH_TEST in config.
 */
class InstagramImageTestController extends Controller
{
    private function assertTestEnabled(): void
    {
        if (! config('app.debug') && ! config('services.instagram.allow_image_publish_test')) {
            abort(404);
        }
    }

    public function index()
    {
        $this->assertTestEnabled();

        /** @var User $user */
        $user = Auth::guard('user')->user();
        $ownerId = (int) ($user->getEffectiveUser()?->id ?? $user->id);

        $accounts = InstagramAccount::query()
            ->where('user_id', $ownerId)
            ->orderBy('username')
            ->get();

        return view('user.instagram-image-test.index', [
            'accounts' => $accounts,
        ]);
    }

    public function publish(Request $request, InstagramGraphService $instagramGraph)
    {
        $this->assertTestEnabled();

        /** @var User $user */
        $user = Auth::guard('user')->user();
        $ownerId = (int) ($user->getEffectiveUser()?->id ?? $user->id);

        $validated = $request->validate([
            'instagram_account_id' => [
                'required',
                'integer',
                Rule::exists('instagram_accounts', 'id')->where('user_id', $ownerId),
            ],
            'image' => ['required', 'image', 'max:15360'],
            'caption' => ['nullable', 'string', 'max:2200'],
        ]);

        $ig = InstagramAccount::query()
            ->where('user_id', $ownerId)
            ->where('id', $validated['instagram_account_id'])
            ->firstOrFail();

        if (empty($ig->ig_user_id)) {
            return back()->withInput()->withErrors([
                'instagram_account_id' => 'This Instagram account is missing ig_user_id. Reconnect the account.',
            ]);
        }

        if (! $ig->validToken()) {
            return back()->withInput()->withErrors([
                'instagram_account_id' => 'Instagram access token expired. Reconnect the account.',
            ]);
        }

        $token = (string) ($ig->getRawOriginal('access_token') ?? $ig->access_token ?? '');
        if ($token === '') {
            return back()->withInput()->withErrors([
                'instagram_account_id' => 'Instagram access token is missing.',
            ]);
        }

        $fileName = saveImage($request->file('image'));
        $caption = isset($validated['caption']) ? trim($validated['caption']) : '';
        if ($caption === '') {
            $caption = 'Image publish test '.now()->toDateTimeString();
        }

        $post = Post::create([
            'user_id' => $user->id,
            'account_id' => $ig->id,
            'social_type' => 'instagram',
            'type' => 'photo',
            'source' => 'instagram_image_test',
            'title' => $caption,
            'comment' => null,
            'image' => $fileName,
            'video' => null,
            'status' => 0,
            'publish_date' => now()->utc()->format('Y-m-d H:i:s'),
            'scheduled' => 0,
        ]);

        try {
            $instagramGraph->publishPost($post->fresh(['instagramAccount']), $token);
        } catch (\Throwable $e) {
            return back()
                ->withInput()
                ->withErrors(['image' => 'Publish raised an exception: '.$e->getMessage()]);
        }

        $post->refresh();

        if ((int) $post->status === 1) {
            return back()->with('test_success', [
                'message' => 'Published successfully to Instagram.',
                'media_id' => $post->post_id,
                'local_post_id' => $post->id,
            ]);
        }

        return back()->withInput()->withErrors([
            'image' => $this->responseErrorMessage($post),
        ]);
    }

    private function responseErrorMessage(Post $post): string
    {
        $raw = $post->getAttributes()['response'] ?? null;
        if ($raw === null || $raw === '') {
            return 'Publish failed (no error details). Check logs and post #'.$post->id.'.';
        }
        $decoded = is_string($raw) ? json_decode($raw, true) : $raw;
        if (is_array($decoded) && ! empty($decoded['error'])) {
            return (string) $decoded['error'];
        }

        return 'Publish failed: '.(is_string($raw) ? $raw : json_encode($decoded));
    }
}
