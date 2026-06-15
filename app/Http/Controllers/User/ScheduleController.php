<?php

namespace App\Http\Controllers\User;

use App\Enums\DraftEnum;
use App\Http\Controllers\Controller;
use App\Jobs\DeleteFacebookPostJob;
use App\Jobs\DeleteThreadsPostJob;
use App\Jobs\PublishFacebookPost;
use App\Jobs\PublishInstagramPost;
use App\Jobs\PublishLinkedInPost;
use App\Jobs\PublishPinterestPost;
use App\Jobs\PublishThreadsPost;
use App\Jobs\PublishTikTokPost;
use App\Jobs\PublishYouTubePost;
use App\Models\Board;
use App\Models\Facebook;
use App\Models\FacebookPost;
use App\Models\InstagramAccount;
use App\Models\InstagramPost;
use App\Models\Linkedin;
use App\Models\Page;
use App\Models\Pinterest;
use App\Models\Post;
use App\Models\PinterestPin;
use App\Models\Tiktok;
use App\Models\TiktokPost;
use App\Models\Thread;
use App\Models\ThreadPost;
use App\Models\Timeslot;
use App\Models\User;
use App\Models\Youtube;
use App\Services\FacebookService;
use App\Services\FeatureUsageService;
use App\Services\PinterestService;
use App\Services\PostService;
use App\Services\SocialMediaLogService;
use App\Services\TikTokService;
use App\Services\YouTubeService;
use App\Services\TimezoneService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ScheduleController extends Controller
{
    private const POSTS_CACHE_TTL_HOURS = 24;

    protected $facebookService;

    protected $pinterestService;

    protected $tiktokService;

    protected $featureUsageService;

    protected $source;

    protected $logService;

    public function __construct(FeatureUsageService $featureUsageService)
    {
        $this->facebookService = new FacebookService;
        $this->pinterestService = new PinterestService;
        $this->tiktokService = new TikTokService;
        $this->featureUsageService = $featureUsageService;
        $this->source = 'schedule';
        $this->logService = new SocialMediaLogService;
    }

    /**
     * Verify that a post's account belongs to the user
     */
    private function verifyPostAccountBelongsToUser(Post $post, User $user): bool
    {
        // Ensure post belongs to user
        if ($post->user_id !== $user->id) {
            return false;
        }

        // Check based on social type (use normalized match for values like "linkedin")
        $socialNorm = strtolower((string) $post->social_type);
        switch ($socialNorm) {
            case 'pinterest':
                $board = Board::where('id', $post->account_id)
                    ->where('user_id', $user->id)
                    ->first();

                return $board !== null;

            case 'facebook':
                $page = Page::where('id', $post->account_id)
                    ->where('user_id', $user->id)
                    ->first();

                return $page !== null;

            case 'tiktok':
                $tiktok = Tiktok::where('id', $post->account_id)
                    ->where('user_id', $user->id)
                    ->first();

                return $tiktok !== null;

            case 'instagram':
                $ownerId = (int) ($user->getEffectiveUser()?->id ?? $user->id);

                return InstagramAccount::where('id', $post->account_id)
                    ->where('user_id', $ownerId)
                    ->exists();

            case 'threads':
                $ownerId = (int) ($user->getEffectiveUser()?->id ?? $user->id);

                return Thread::where('id', $post->account_id)
                    ->where('user_id', $ownerId)
                    ->exists();

            case 'linkedin':
                $ownerId = (int) ($user->getEffectiveUser()?->id ?? $user->id);

                return Linkedin::where('id', $post->account_id)
                    ->where('user_id', $ownerId)
                    ->exists();

            case 'youtube':
                $ownerId = (int) ($user->getEffectiveUser()?->id ?? $user->id);

                return Youtube::where('id', $post->account_id)
                    ->where('user_id', $ownerId)
                    ->exists();

            default:
                return false;
        }
    }

    /**
     * Normalize create-post uploads: single file, or multiple files for Instagram/Threads carousel.
     *
     * @return array{error: ?array, has_files: bool, image: ?string, images: array<int, string>, video: ?string, instagram_carousel_items: array<int, array{type: string, path: string}>}
     */
    private function normalizeCreatePostFileUploads(Request $request): array
    {
        $empty = [
            'error' => null,
            'has_files' => false,
            'image' => null,
            'images' => [],
            'video' => null,
            'document' => null,
            'document_name' => null,
            'instagram_carousel_items' => [],
        ];

        $raw = $request->file('files');
        if ($raw === null) {
            return $empty;
        }

        $igFormat = strtolower((string) $request->input('instagram_content_format', ''));
        $threadsFormat = strtolower((string) $request->input('threads_content_format', ''));
        if ($threadsFormat === '') {
            $threadsRaw = $request->input('threads_content_formats');
            if (is_string($threadsRaw) && $threadsRaw !== '') {
                $decoded = json_decode($threadsRaw, true);
                if (is_array($decoded) && in_array('carousel', array_map(fn($v) => strtolower((string) $v), $decoded), true)) {
                    $threadsFormat = 'carousel';
                }
            }
        }
        $linkedinFormat = strtolower((string) $request->input('linkedin_content_format', ''));
        if ($linkedinFormat === '') {
            $linkedinRaw = $request->input('linkedin_content_formats');
            if (is_string($linkedinRaw) && $linkedinRaw !== '') {
                $decoded = json_decode($linkedinRaw, true);
                if (is_array($decoded)) {
                    $normalized = array_map(fn($v) => strtolower((string) $v), $decoded);
                    if (in_array('document', $normalized, true)) {
                        $linkedinFormat = 'document';
                    }
                }
            }
        }
        $files = is_array($raw) ? array_values(array_filter($raw)) : [$raw];

        if ($igFormat === 'carousel' || $threadsFormat === 'carousel') {
            if (count($files) < 2) {
                return [
                    'error' => [
                        'success' => false,
                        'message' => 'Carousel requires at least 2 media files.',
                    ],
                    'has_files' => false,
                    'image' => null,
                    'images' => [],
                    'video' => null,
                    'document' => null,
                    'document_name' => null,
                    'instagram_carousel_items' => [],
                ];
            }
            $maxCarousel = $threadsFormat === 'carousel' ? 20 : 10;
            if (count($files) > $maxCarousel) {
                return [
                    'error' => [
                        'success' => false,
                        'message' => 'Carousel allows at most ' . $maxCarousel . ' media files.',
                    ],
                    'has_files' => false,
                    'image' => null,
                    'images' => [],
                    'video' => null,
                    'document' => null,
                    'document_name' => null,
                    'instagram_carousel_items' => [],
                ];
            }
            $items = [];
            foreach ($files as $file) {
                if (! $file instanceof UploadedFile || ! $file->isValid()) {
                    return [
                        'error' => [
                            'success' => false,
                            'message' => 'One or more carousel uploads are invalid.',
                        ],
                        'has_files' => false,
                        'image' => null,
                        'images' => [],
                        'video' => null,
                        'document' => null,
                        'document_name' => null,
                        'instagram_carousel_items' => [],
                    ];
                }
                $ext = strtolower((string) ($file->getClientOriginalExtension() ?: ''));
                $isVideo = in_array($ext, ['mp4', 'mkv', 'mov', 'mpeg', 'webm'], true);
                if ($isVideo) {
                    $items[] = ['type' => 'video', 'path' => saveToS3($file)];
                } else {
                    $items[] = ['type' => 'image', 'path' => saveImage($file)];
                }
            }

            return [
                'error' => null,
                'has_files' => true,
                'image' => null,
                'images' => [],
                'video' => null,
                'document' => null,
                'document_name' => null,
                'instagram_carousel_items' => $items,
            ];
        }

        if ($linkedinFormat === 'document') {
            $file = $files[0] ?? null;
            if (! $file instanceof UploadedFile || ! $file->isValid()) {
                return $empty;
            }
            $ext = strtolower((string) ($file->getClientOriginalExtension() ?: ''));
            if (! in_array($ext, ['pdf', 'doc', 'docx', 'ppt', 'pptx'], true)) {
                return [
                    'error' => ['success' => false, 'message' => 'LinkedIn document post requires a PDF, DOC, DOCX, PPT, or PPTX file.'],
                    'has_files' => false,
                    'image' => null,
                    'images' => [],
                    'video' => null,
                    'document' => null,
                    'document_name' => null,
                    'instagram_carousel_items' => [],
                ];
            }

            return [
                'error' => null,
                'has_files' => true,
                'image' => null,
                'images' => [],
                'video' => null,
                'document' => saveToS3($file),
                'document_name' => (string) ($file->getClientOriginalName() ?: 'document.' . $ext),
                'instagram_carousel_items' => [],
            ];
        }

        $file = $files[0] ?? null;
        if (! $file instanceof UploadedFile || ! $file->isValid()) {
            return $empty;
        }
        if ($request->video) {
            return [
                'error' => null,
                'has_files' => true,
                'image' => null,
                'images' => [],
                'video' => saveToS3($file),
                'document' => null,
                'document_name' => null,
                'instagram_carousel_items' => [],
            ];
        }
        $img = saveImage($file);

        return [
            'error' => null,
            'has_files' => true,
            'image' => $img,
            'images' => [$img],
            'video' => null,
            'document' => null,
            'document_name' => null,
            'instagram_carousel_items' => [],
        ];
    }

    /**
     * Map Instagram format + uploads to post row (type, image, video, metadata).
     *
     * @param  array<string, mixed>  $upload Output of normalizeCreatePostFileUploads()
     * @return array{success: bool, message?: string, type?: string, image?: ?string, video?: ?string, metadata?: ?string}
     */
    private function instagramComposePlan(Request $request, array $upload, ?string $formatForced = null): array
    {
        if (empty($upload['has_files'])) {
            return ['success' => false, 'message' => 'Instagram posts require an image or video.'];
        }

        if (! empty($upload['instagram_carousel_items'])) {
            $items = $upload['instagram_carousel_items'];
            $metaRows = [];
            foreach ($items as $it) {
                if (($it['type'] ?? '') === 'video') {
                    $metaRows[] = ['image' => null, 'video' => $it['path']];
                } else {
                    $metaRows[] = ['image' => $it['path'], 'video' => null];
                }
            }

            return [
                'success' => true,
                'type' => 'carousel',
                'image' => null,
                'video' => null,
                'metadata' => json_encode(['ig_carousel' => $metaRows]),
            ];
        }

        $format = strtolower((string) ($formatForced ?? $request->input('instagram_content_format', 'post')));
        if ($format === 'reel') {
            $format = 'post';
        }
        if ($format === 'carousel' && empty($upload['instagram_carousel_items'])) {
            return ['success' => false, 'message' => 'Instagram carousel requires at least 2 media files.'];
        }
        $hasVideo = ! empty($upload['video']);
        $hasImage = ! empty($upload['image']);

        if ($format === 'story') {
            if (! $hasVideo && ! $hasImage) {
                return ['success' => false, 'message' => 'Instagram stories require an image or video.'];
            }

            return [
                'success' => true,
                'type' => 'story',
                'image' => $upload['image'],
                'video' => $upload['video'],
                'metadata' => null,
            ];
        }

        if ($hasVideo) {
            return [
                'success' => true,
                'type' => 'video',
                'image' => null,
                'video' => $upload['video'],
                'metadata' => null,
            ];
        }

        if ($hasImage) {
            return [
                'success' => true,
                'type' => 'photo',
                'image' => $upload['image'],
                'video' => null,
                'metadata' => null,
            ];
        }

        return ['success' => false, 'message' => 'Instagram posts require an image or video.'];
    }

    /**
     * Instagram targets from compose modal (instagram_content_formats JSON), before expanding to concrete plans.
     *
     * @param  array<string, mixed>  $upload
     * @return list<string>
     */
    private function instagramContentFormatsFromRequest(Request $request, array $upload): array
    {
        if (! empty($upload['instagram_carousel_items'])) {
            return ['carousel'];
        }

        $raw = $request->input('instagram_content_formats');
        $selected = [];
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $selected = $decoded;
            }
        } elseif (is_array($raw)) {
            $selected = $raw;
        }

        $selected = array_values(array_unique(array_map('strval', $selected)));
        $hasVideo = ! empty($upload['video']);
        $hasImage = ! empty($upload['image']);
        $out = [];

        foreach ($selected as $f) {
            $f = strtolower(trim($f));
            if ($f === 'carousel') {
                continue;
            }
            if ($f === 'reel') {
                continue;
            }
            if ($f === 'story') {
                $out[] = 'story';

                continue;
            }
            if ($f === 'post') {
                $out[] = 'post';
            }
        }

        $out = array_values(array_unique($out));

        if ($out === []) {
            if ($selected === []) {
                return ['post'];
            }
            $nonReel = array_values(array_filter($selected, function ($f) {
                return strtolower(trim((string) $f)) !== 'reel';
            }));

            return $nonReel === [] ? ['post'] : [];
        }

        return $out;
    }

    /**
     * Create a Post row for Instagram from the compose modal / queue flows.
     *
     * @param  object  $account  InstagramAccount model (has ->id)
     * @return array{error: ?string, post: ?Post, plan: ?array<string, mixed>}
     */
    private function createInstagramPostFromCompose(User $user, object $account, Request $request, array $upload, string $publishDateTime, int $scheduled = 0, ?string $instagramFormatOverride = null): array
    {
        if (! $upload['has_files']) {
            return ['error' => 'Instagram publishing requires an image or video.', 'post' => null, 'plan' => null];
        }

        $plan = $this->instagramComposePlan($request, $upload, $instagramFormatOverride);
        if (! $plan['success']) {
            return ['error' => $plan['message'] ?? 'Invalid Instagram post.', 'post' => null, 'plan' => null];
        }

        $ownerId = (int) ($user->getEffectiveUser()?->id ?? $user->id);
        $igRow = InstagramAccount::query()
            ->where('id', $account->id)
            ->where('user_id', $ownerId)
            ->first();

        if (! $igRow) {
            return ['error' => 'Instagram account not found.', 'post' => null, 'plan' => null];
        }

        if (! $igRow->validToken()) {
            return ['error' => 'Instagram access token expired. Reconnect your Instagram account.', 'post' => null, 'plan' => null];
        }

        $data = [
            'user_id' => $user->id,
            'account_id' => $account->id,
            'social_type' => 'instagram',
            'type' => $plan['type'],
            'source' => $this->source,
            'title' => $request->get('content'),
            'comment' => $request->get('comment'),
            'image' => $plan['image'] ?? null,
            'video' => $plan['video'] ?? null,
            'status' => 0,
            'publish_date' => $publishDateTime,
            'scheduled' => $scheduled,
        ];

        if (! empty($plan['metadata'])) {
            $data['metadata'] = $plan['metadata'];
        }

        $post = PostService::create($data);

        if ($this->verifyPostAccountBelongsToUser($post, $user)) {
            $user->incrementFeatureUsage('scheduled_posts_per_account', 1);
        }

        return ['error' => null, 'post' => $post, 'plan' => $plan];
    }

    /**
     * @return array{error: ?string, post: ?Post, plan: ?array<string, mixed>}
     */
    private function createThreadsPostFromCompose(User $user, object $account, Request $request, array $upload, string $publishDateTime, int $scheduled = 0): array
    {
        $ownerId = (int) ($user->getEffectiveUser()?->id ?? $user->id);
        $threadRow = Thread::query()
            ->where('id', $account->id)
            ->where('user_id', $ownerId)
            ->first();
        if (! $threadRow) {
            return ['error' => 'Threads account not found.', 'post' => null, 'plan' => null];
        }
        if (! $threadRow->validToken()) {
            return ['error' => 'Threads access token expired. Reconnect your Threads account.', 'post' => null, 'plan' => null];
        }

        $plan = [
            'type' => 'content_only',
            'image' => null,
            'video' => null,
            'metadata' => null,
        ];

        $threadsFormats = ['post'];
        $threadsRaw = $request->input('threads_content_formats');
        if (is_array($threadsRaw)) {
            $threadsFormats = array_values(array_filter(array_map(fn($v) => strtolower((string) $v), $threadsRaw)));
        } elseif (is_string($threadsRaw) && $threadsRaw !== '') {
            $decoded = json_decode($threadsRaw, true);
            if (is_array($decoded)) {
                $threadsFormats = array_values(array_filter(array_map(fn($v) => strtolower((string) $v), $decoded)));
            }
        }
        $threadsSingle = strtolower((string) $request->input('threads_content_format', ''));
        if ($threadsSingle !== '' && ! in_array($threadsSingle, $threadsFormats, true)) {
            $threadsFormats[] = $threadsSingle;
        }
        if ($threadsFormats === []) {
            $threadsFormats = ['post'];
        }
        $threadsFormat = in_array('carousel', $threadsFormats, true) ? 'carousel' : 'post';

        if ($threadsFormat === 'carousel') {
            if (($upload['instagram_carousel_items'] ?? []) === []) {
                return ['error' => 'Threads carousel requires at least 2 media files.', 'post' => null, 'plan' => null];
            }
            if (count($upload['instagram_carousel_items']) < 2) {
                return ['error' => 'Threads carousel requires at least 2 media files.', 'post' => null, 'plan' => null];
            }
            $carousel = [];
            foreach ($upload['instagram_carousel_items'] as $item) {
                if (! is_array($item)) {
                    continue;
                }
                if (($item['type'] ?? '') === 'video' && ! empty($item['path'])) {
                    $carousel[] = ['video' => $item['path']];
                } elseif (! empty($item['path'])) {
                    $carousel[] = ['image' => $item['path']];
                }
            }
            if (count($carousel) > 20) {
                return ['error' => 'Threads carousel supports at most 20 media files.', 'post' => null, 'plan' => null];
            }
            if (count($carousel) >= 2) {
                $plan['type'] = 'carousel';
                $plan['metadata'] = json_encode(['threads_carousel' => $carousel]);
            } else {
                return ['error' => 'Threads carousel requires at least 2 valid media files.', 'post' => null, 'plan' => null];
            }
        } else {
            if (! empty($upload['video'])) {
                $plan['type'] = 'video';
                $plan['video'] = $upload['video'];
            } elseif (! empty($upload['image'])) {
                $plan['type'] = 'photo';
                $plan['image'] = $upload['image'];
            } elseif (($upload['instagram_carousel_items'] ?? []) !== []) {
                $first = $upload['instagram_carousel_items'][0] ?? null;
                if (is_array($first) && (($first['type'] ?? '') === 'video') && ! empty($first['path'])) {
                    $plan['type'] = 'video';
                    $plan['video'] = $first['path'];
                } elseif (is_array($first) && ! empty($first['path'])) {
                    $plan['type'] = 'photo';
                    $plan['image'] = $first['path'];
                }
            }
        }

        $data = [
            'user_id' => $user->id,
            'account_id' => $account->id,
            'social_type' => 'threads',
            'type' => $plan['type'],
            'source' => $this->source,
            'title' => $request->get('content'),
            'comment' => $request->get('comment'),
            'image' => $plan['image'],
            'video' => $plan['video'],
            'status' => 0,
            'publish_date' => $publishDateTime,
            'scheduled' => $scheduled,
            'metadata' => $plan['metadata'],
        ];

        $post = PostService::create($data);
        if ($this->verifyPostAccountBelongsToUser($post, $user)) {
            $user->incrementFeatureUsage('scheduled_posts_per_account', 1);
        }

        return ['error' => null, 'post' => $post, 'plan' => $plan];
    }

    private function linkedinContentFormatFromRequest(Request $request): string
    {
        $formats = ['post'];
        $raw = $request->input('linkedin_content_formats');
        if (is_array($raw)) {
            $formats = array_values(array_filter(array_map(fn($v) => strtolower((string) $v), $raw)));
        } elseif (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $formats = array_values(array_filter(array_map(fn($v) => strtolower((string) $v), $decoded)));
            }
        }
        $single = strtolower((string) $request->input('linkedin_content_format', ''));
        if ($single !== '' && ! in_array($single, $formats, true)) {
            $formats[] = $single;
        }
        if ($formats === []) {
            $formats = ['post'];
        }
        if (in_array('document', $formats, true)) {
            return 'document';
        }

        return 'post';
    }

    private function linkedinComposePlan(Request $request, array $upload): array
    {
        $format = $this->linkedinContentFormatFromRequest($request);
        $plan = [
            'type' => 'content_only',
            'image' => null,
            'video' => null,
            'metadata' => null,
        ];

        if ($format === 'document') {
            if (empty($upload['document'])) {
                return ['success' => false, 'message' => 'LinkedIn document post requires a document file.'];
            }
            $plan['type'] = 'document';
            $plan['metadata'] = json_encode([
                'linkedin_document' => (string) $upload['document'],
                'linkedin_document_name' => (string) ($upload['document_name'] ?? ''),
            ]);

            return ['success' => true] + $plan;
        }

        if (! empty($upload['video'])) {
            $plan['type'] = 'video';
            $plan['video'] = $upload['video'];
        } elseif (! empty($upload['image'])) {
            $plan['type'] = 'photo';
            $plan['image'] = $upload['image'];
        } elseif (! empty($upload['document'])) {
            return ['success' => false, 'message' => 'Select LinkedIn Document post type for document uploads.'];
        }

        return ['success' => true] + $plan;
    }

    private function youtubeMetadataFromRequest(Request $request): array
    {
        return [
            'privacy_status' => $request->input('youtube_privacy_status', 'public'),
        ];
    }

    /**
     * Check if user can create scheduled posts
     *
     * @param  int  $newPostsCount  Number of new posts to be created
     * @return array ['allowed' => bool, 'message' => string, 'usage' => int, 'limit' => int|null, 'remaining' => int|null]
     */
    private function checkScheduledPostsLimit(User $user, int $newPostsCount = 1): array
    {
        // Check if user can use the feature
        if (! $user->canUseFeature('scheduled_posts_per_account')) {
            return [
                'allowed' => false,
                'message' => 'Scheduled posts feature is not available in your package.',
                'usage' => 0,
                'limit' => null,
                'remaining' => null,
            ];
        }

        // Get usage stats
        $usageStats = $this->featureUsageService->getUsageStats($user, 'scheduled_posts_per_account');

        if (empty($usageStats)) {
            return [
                'allowed' => true,
                'message' => 'Feature limit check passed.',
                'usage' => 0,
                'limit' => null,
                'remaining' => null,
            ];
        }

        $currentUsage = $usageStats['current_usage'] ?? 0;
        $limit = $usageStats['limit'] ?? null;
        $isUnlimited = $usageStats['is_unlimited'] ?? false;

        // If unlimited, allow
        if ($isUnlimited || $limit === null) {
            return [
                'allowed' => true,
                'message' => 'Feature limit check passed.',
                'usage' => $currentUsage,
                'limit' => null,
                'remaining' => null,
            ];
        }

        // Check if adding new posts would exceed the limit
        $totalAfterAdding = $currentUsage + $newPostsCount;

        if ($totalAfterAdding > $limit) {
            $remaining = max(0, $limit - $currentUsage);

            return [
                'allowed' => false,
                'message' => "You have reached your limit of {$limit} scheduled posts per account. You have {$remaining} remaining. Please upgrade your package to schedule more posts.",
                'usage' => $currentUsage,
                'limit' => $limit,
                'remaining' => $remaining,
            ];
        }

        return [
            'allowed' => true,
            'message' => 'Feature limit check passed.',
            'usage' => $currentUsage,
            'limit' => $limit,
            'remaining' => $limit - $totalAfterAdding,
        ];
    }

    // new design code
    public function index()
    {
        $user = User::with('boards.pinterest', 'pages.facebook', 'tiktok', 'instagramAccounts', 'threads', 'linkedins', 'youtubes', 'timezone')->find(Auth::guard('user')->id());
        $accounts = $user->getAccounts();
        $accounts = $this->sortAccountsByRecentUsage($accounts, $user->id);
        $userTimezoneName = $user->timezone && ! empty($user->timezone->name) ? $user->timezone->name : 'UTC';
        $canAccessAnalytics = $user->canAccessMenu(8) && $user->hasMenuAccess('analytics');
        $scheduleSelectedAccount = $user->schedule_selected_account;

        return view('user.schedule-new-design.index', compact('accounts', 'userTimezoneName', 'canAccessAnalytics', 'scheduleSelectedAccount'));
    }

    /**
     * Sort accounts for sidebar: frequently used (posts in recent days) first, then remaining by name ascending.
     */
    protected function sortAccountsByRecentUsage($accounts, $userId, $recentDays = 7)
    {
        $countRows = Post::where('user_id', $userId)
            ->where('created_at', '>=', Carbon::now()->subDays($recentDays))
            ->selectRaw('account_id, social_type, count(*) as post_count')
            ->groupBy('account_id', 'social_type')
            ->get();

        $postCounts = [];
        foreach ($countRows as $row) {
            $key = (string) $row->social_type . '_' . (int) $row->account_id;
            $postCounts[$key] = (int) $row->post_count;
        }

        $sortSegment = function ($segment) use ($postCounts) {
            $segment = $segment->values();
            $sorted = $segment->sortByDesc(function ($account) use ($postCounts) {
                $key = ($account->type ?? '') . '_' . (int) $account->id;

                return $postCounts[$key] ?? 0;
            })->values();

            $withPosts = $sorted->filter(function ($a) use ($postCounts) {
                $key = ($a->type ?? '') . '_' . (int) $a->id;

                return ($postCounts[$key] ?? 0) > 0;
            });
            $withoutPosts = $sorted->filter(function ($a) use ($postCounts) {
                $key = ($a->type ?? '') . '_' . (int) $a->id;

                return ($postCounts[$key] ?? 0) === 0;
            })->sortBy('name')->values();

            return $withPosts->concat($withoutPosts);
        };

        $facebook = $sortSegment($accounts->filter(fn($a) => ($a->type ?? '') === 'facebook'));
        $pinterest = $sortSegment($accounts->filter(fn($a) => ($a->type ?? '') === 'pinterest'));
        $tiktok = $sortSegment($accounts->filter(fn($a) => ($a->type ?? '') === 'tiktok'));
        $instagram = $sortSegment($accounts->filter(fn($a) => ($a->type ?? '') === 'instagram'));
        $threads = $sortSegment($accounts->filter(fn($a) => ($a->type ?? '') === 'threads'));
        $linkedin = $sortSegment($accounts->filter(fn($a) => ($a->type ?? '') === 'linkedin'));
        $youtube = $sortSegment($accounts->filter(fn($a) => ($a->type ?? '') === 'youtube'));

        return $facebook->concat($pinterest)->concat($tiktok)->concat($instagram)->concat($threads)->concat($linkedin)->concat($youtube);
    }

    /**
     * Get the account used for the most recently created post (last used account)
     * and the schedule_status of all Facebook pages for dynamic updates
     */
    public function getLastUsedAccount()
    {
        $userId = Auth::guard('user')->id();
        $user = User::find($userId);
        $post = Post::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->first();

        $account = null;
        if ($post) {
            $st = $post->social_type ?? '';
            if (str_contains($st, 'facebook')) {
                $page = Page::with('facebook')->find($post->account_id);
                if ($page) {
                    $account = [
                        'id' => $page->id,
                        'type' => 'facebook',
                        'name' => $page->name,
                        'profile_image' => $page->profile_image,
                    ];
                }
            } elseif (str_contains($st, 'pinterest')) {
                $board = Board::with('pinterest')->find($post->account_id);
                if ($board) {
                    $account = [
                        'id' => $board->id,
                        'type' => 'pinterest',
                        'name' => $board->name,
                        'profile_image' => $board->pinterest?->profile_image ?? '',
                    ];
                }
            } elseif (str_contains($st, 'tiktok')) {
                $tiktok = Tiktok::find($post->account_id);
                if ($tiktok) {
                    $account = [
                        'id' => $tiktok->id,
                        'type' => 'tiktok',
                        'name' => $tiktok->display_name ?? $tiktok->username,
                        'profile_image' => $tiktok->profile_image ?? '',
                    ];
                }
            } elseif (str_contains($st, 'instagram')) {
                $ig = InstagramAccount::find($post->account_id);
                if ($ig) {
                    $account = [
                        'id' => $ig->id,
                        'type' => 'instagram',
                        'name' => $ig->name ?: ($ig->username ? '@' . $ig->username : 'Instagram'),
                        'profile_image' => $ig->profile_image ?? '',
                    ];
                }
            } elseif (str_contains($st, 'threads')) {
                $thread = Thread::find($post->account_id);
                if ($thread) {
                    $account = [
                        'id' => $thread->id,
                        'type' => 'threads',
                        'name' => $thread->username ? '@' . $thread->username : 'Threads',
                        'profile_image' => $thread->profile_image ?? '',
                    ];
                }
            } elseif (str_contains($st, 'linkedin')) {
                $linkedinRow = Linkedin::find($post->account_id);
                if ($linkedinRow) {
                    $account = [
                        'id' => $linkedinRow->id,
                        'type' => 'linkedin',
                        'name' => $linkedinRow->username ? '@' . $linkedinRow->username : 'LinkedIn',
                        'profile_image' => $linkedinRow->profile_image ?? '',
                    ];
                }
            } elseif (str_contains($st, 'youtube')) {
                $youtubeRow = Youtube::find($post->account_id);
                if ($youtubeRow) {
                    $account = [
                        'id' => $youtubeRow->id,
                        'type' => 'youtube',
                        'name' => $youtubeRow->username ?: 'YouTube',
                        'profile_image' => $youtubeRow->profile_image ?? '',
                    ];
                }
            }
        }

        $accountsStatus = collect();
        if ($user) {
            $user->pages()->get(['id', 'schedule_status'])->each(function ($page) use ($accountsStatus) {
                $accountsStatus->push([
                    'id' => $page->id,
                    'type' => 'facebook',
                    'schedule_status' => $page->schedule_status ?? 'inactive',
                ]);
            });
            $user->boards()->get(['id', 'schedule_status'])->each(function ($board) use ($accountsStatus) {
                $accountsStatus->push([
                    'id' => $board->id,
                    'type' => 'pinterest',
                    'schedule_status' => $board->schedule_status ?? 'inactive',
                ]);
            });
            $user->tiktok()->get(['id', 'schedule_status'])->each(function ($tiktokRow) use ($accountsStatus) {
                $accountsStatus->push([
                    'id' => $tiktokRow->id,
                    'type' => 'tiktok',
                    'schedule_status' => $tiktokRow->schedule_status ?? 'inactive',
                ]);
            });
            $user->instagramAccounts()->get(['id', 'schedule_status'])->each(function ($igRow) use ($accountsStatus) {
                $accountsStatus->push([
                    'id' => $igRow->id,
                    'type' => 'instagram',
                    'schedule_status' => $igRow->schedule_status ?? 'inactive',
                ]);
            });
            $user->threads()->get(['id', 'schedule_status'])->each(function ($threadRow) use ($accountsStatus) {
                $accountsStatus->push([
                    'id' => $threadRow->id,
                    'type' => 'threads',
                    'schedule_status' => $threadRow->schedule_status ?? 'inactive',
                ]);
            });
            $user->linkedins()->get(['id', 'schedule_status'])->each(function ($linkedinRow) use ($accountsStatus) {
                $accountsStatus->push([
                    'id' => $linkedinRow->id,
                    'type' => 'linkedin',
                    'schedule_status' => $linkedinRow->schedule_status ?? 'inactive',
                ]);
            });
            $user->youtubes()->get(['id', 'schedule_status'])->each(function ($youtubeRow) use ($accountsStatus) {
                $accountsStatus->push([
                    'id' => $youtubeRow->id,
                    'type' => 'youtube',
                    'schedule_status' => $youtubeRow->schedule_status ?? 'inactive',
                ]);
            });
        }

        return response()->json([
            'success' => true,
            'account' => $account,
            'accounts_status' => $accountsStatus->toArray(),
        ]);
    }

    /**
     * Fetch all user accounts with their schedule_status for the create post modal.
     * Used when New Post button is clicked to sync dropdown selection with server state.
     */
    public function getAccountsWithStatus()
    {
        $user = User::find(Auth::guard('user')->id());
        $accountsStatus = collect();

        $user->pages()->get(['id', 'schedule_status'])->each(function ($page) use ($accountsStatus) {
            $accountsStatus->push([
                'id' => $page->id,
                'type' => 'facebook',
                'schedule_status' => $page->schedule_status ?? 'inactive',
            ]);
        });
        $user->boards()->get(['id', 'schedule_status'])->each(function ($board) use ($accountsStatus) {
            $accountsStatus->push([
                'id' => $board->id,
                'type' => 'pinterest',
                'schedule_status' => $board->schedule_status ?? 'inactive',
            ]);
        });
        $user->tiktok()->get(['id', 'schedule_status'])->each(function ($tiktok) use ($accountsStatus) {
            $accountsStatus->push([
                'id' => $tiktok->id,
                'type' => 'tiktok',
                'schedule_status' => $tiktok->schedule_status ?? 'inactive',
            ]);
        });
        $user->instagramAccounts()->get(['id', 'schedule_status'])->each(function ($ig) use ($accountsStatus) {
            $accountsStatus->push([
                'id' => $ig->id,
                'type' => 'instagram',
                'schedule_status' => $ig->schedule_status ?? 'inactive',
            ]);
        });
        $user->threads()->get(['id', 'schedule_status'])->each(function ($threadRow) use ($accountsStatus) {
            $accountsStatus->push([
                'id' => $threadRow->id,
                'type' => 'threads',
                'schedule_status' => $threadRow->schedule_status ?? 'inactive',
            ]);
        });
        $user->linkedins()->get(['id', 'schedule_status'])->each(function ($linkedinRow) use ($accountsStatus) {
            $accountsStatus->push([
                'id' => $linkedinRow->id,
                'type' => 'linkedin',
                'schedule_status' => $linkedinRow->schedule_status ?? 'inactive',
            ]);
        });
        $user->youtubes()->get(['id', 'schedule_status'])->each(function ($youtubeRow) use ($accountsStatus) {
            $accountsStatus->push([
                'id' => $youtubeRow->id,
                'type' => 'youtube',
                'schedule_status' => $youtubeRow->schedule_status ?? 'inactive',
            ]);
        });

        $account = null;
        $post = Post::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->first();
        if ($post) {
            $st = $post->social_type ?? '';
            if (str_contains($st, 'facebook')) {
                $page = Page::with('facebook')->find($post->account_id);
                if ($page) {
                    $account = [
                        'id' => $page->id,
                        'type' => 'facebook',
                        'name' => $page->name,
                        'profile_image' => $page->profile_image,
                    ];
                }
            } elseif (str_contains($st, 'pinterest')) {
                $board = Board::with('pinterest')->find($post->account_id);
                if ($board) {
                    $account = [
                        'id' => $board->id,
                        'type' => 'pinterest',
                        'name' => $board->name,
                        'profile_image' => $board->pinterest?->profile_image ?? '',
                    ];
                }
            } elseif (str_contains($st, 'tiktok')) {
                $tiktokRow = Tiktok::find($post->account_id);
                if ($tiktokRow) {
                    $account = [
                        'id' => $tiktokRow->id,
                        'type' => 'tiktok',
                        'name' => $tiktokRow->display_name ?? $tiktokRow->username,
                        'profile_image' => $tiktokRow->profile_image ?? '',
                    ];
                }
            } elseif (str_contains($st, 'instagram')) {
                $igRow = InstagramAccount::find($post->account_id);
                if ($igRow) {
                    $account = [
                        'id' => $igRow->id,
                        'type' => 'instagram',
                        'name' => $igRow->name ?: ($igRow->username ? '@' . $igRow->username : 'Instagram'),
                        'profile_image' => $igRow->profile_image ?? '',
                    ];
                }
            } elseif (str_contains($st, 'threads')) {
                $thread = Thread::find($post->account_id);
                if ($thread) {
                    $account = [
                        'id' => $thread->id,
                        'type' => 'threads',
                        'name' => $thread->username ? '@' . $thread->username : 'Threads',
                        'profile_image' => $thread->profile_image ?? '',
                    ];
                }
            } elseif (str_contains($st, 'linkedin')) {
                $linkedinRow = Linkedin::find($post->account_id);
                if ($linkedinRow) {
                    $account = [
                        'id' => $linkedinRow->id,
                        'type' => 'linkedin',
                        'name' => $linkedinRow->username ? '@' . $linkedinRow->username : 'LinkedIn',
                        'profile_image' => $linkedinRow->profile_image ?? '',
                    ];
                }
            } elseif (str_contains($st, 'youtube')) {
                $youtubeRow = Youtube::find($post->account_id);
                if ($youtubeRow) {
                    $account = [
                        'id' => $youtubeRow->id,
                        'type' => 'youtube',
                        'name' => $youtubeRow->username ?: 'YouTube',
                        'profile_image' => $youtubeRow->profile_image ?? '',
                    ];
                }
            }
        }

        return response()->json([
            'success' => true,
            'account' => $account,
            'accounts_status' => $accountsStatus->toArray(),
        ]);
    }

    /**
     * Fetch live TikTok creator info for Direct Post UX compliance.
     */
    public function getTikTokCreatorInfo(Request $request)
    {
        $request->validate([
            'account_id' => 'required|integer',
        ]);

        $user = Auth::guard('user')->user();
        $account = Tiktok::where('id', $request->integer('account_id'))
            ->where('user_id', $user->id)
            ->first();

        if (! $account) {
            return response()->json([
                'success' => false,
                'message' => 'TikTok account not found.',
            ]);
        }

        $tokenResponse = TikTokService::validateToken($account);
        if (! $tokenResponse['success']) {
            return response()->json([
                'success' => false,
                'message' => $tokenResponse['message'] ?? 'Failed to validate TikTok access token.',
            ]);
        }

        $creatorInfoResponse = $this->tiktokService->queryCreatorInfo($tokenResponse['access_token']);
        if (! $creatorInfoResponse['success']) {
            return response()->json([
                'success' => false,
                'message' => $creatorInfoResponse['message'] ?? 'Failed to fetch TikTok creator info.',
            ]);
        }

        $creatorInfo = $creatorInfoResponse['data'] ?? [];
        $privacyOptions = $creatorInfo['privacy_level_options'] ?? [];
        if (! is_array($privacyOptions)) {
            $privacyOptions = [];
        }

        $canPost = true;
        if (array_key_exists('can_post', $creatorInfo)) {
            $canPost = (bool) $creatorInfo['can_post'];
        } elseif (array_key_exists('can_post_content', $creatorInfo)) {
            $canPost = (bool) $creatorInfo['can_post_content'];
        }

        $canComment = (bool) ($creatorInfo['comment_enabled'] ?? ! ($creatorInfo['comment_disabled'] ?? false));
        $canDuet = (bool) ($creatorInfo['duet_enabled'] ?? ! ($creatorInfo['duet_disabled'] ?? false));
        $canStitch = (bool) ($creatorInfo['stitch_enabled'] ?? ! ($creatorInfo['stitch_disabled'] ?? false));

        return response()->json([
            'success' => true,
            'data' => [
                'account_id' => $account->id,
                'display_name' => $creatorInfo['creator_nickname'] ?? $account->display_name ?? $account->username,
                'username' => $account->username,
                'can_post' => $canPost,
                'can_post_reason' => $creatorInfo['can_not_post_reason'] ?? $creatorInfo['can_post_message'] ?? null,
                'privacy_level_options' => array_values($privacyOptions),
                'max_video_post_duration_sec' => isset($creatorInfo['max_video_post_duration_sec'])
                    ? (int) $creatorInfo['max_video_post_duration_sec']
                    : null,
                'comment_enabled' => $canComment,
                'duet_enabled' => $canDuet,
                'stitch_enabled' => $canStitch,
            ],
        ]);
    }

    /**
     * Get the user's saved schedule selected account from the database.
     */
    public function getSelectedAccount()
    {
        $user = Auth::guard('user')->user();
        $data = $user->schedule_selected_account;

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Save the user's schedule selected account to the database.
     */
    public function saveSelectedAccount(Request $request)
    {
        $request->validate([
            'type' => 'required|string|in:all,facebook,pinterest,tiktok,instagram,threads,linkedin,youtube',
            'id' => 'nullable|string',
        ]);
        $user = User::find(Auth::guard('user')->id());
        $data = [
            'type' => $request->type,
            'id' => $request->id,
        ];
        $user->update(['schedule_selected_account' => $data]);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Summary of accountStatus
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function accountStatus(Request $request)
    {
        $type = $request->type;
        $id = $request->id;
        $status = $request->status;
        if ($type == 'facebook') {
            $page = Page::find($id);
            if ($page) {
                $page->schedule_status = $status == 1 ? 'active' : 'inactive';
                $page->save();
                $response = [
                    'success' => true,
                    'message' => 'Status changed Successfully!',
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Something went Wrong!',
                ];
            }
        } elseif ($type == 'pinterest') {
            $board = Board::find($id);
            if ($board) {
                $board->schedule_status = $status == 1 ? 'active' : 'inactive';
                $board->save();
                $response = [
                    'success' => true,
                    'message' => 'Status changed Successfully!',
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Something went Wrong!',
                ];
            }
        } elseif ($type == 'tiktok') {
            $tiktok = Tiktok::find($id);
            if ($tiktok) {
                $tiktok->schedule_status = $status == 1 ? 'active' : 'inactive';
                $tiktok->save();
                $response = [
                    'success' => true,
                    'message' => 'Status changed Successfully!',
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Something went Wrong!',
                ];
            }
        } elseif ($type == 'instagram') {
            $ig = InstagramAccount::find($id);
            if ($ig) {
                $ig->schedule_status = $status == 1 ? 'active' : 'inactive';
                $ig->save();
                $response = [
                    'success' => true,
                    'message' => 'Status changed Successfully!',
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Something went Wrong!',
                ];
            }
        } elseif ($type == 'threads') {
            $thread = Thread::find($id);
            if ($thread) {
                $thread->schedule_status = $status == 1 ? 'active' : 'inactive';
                $thread->save();
                $response = [
                    'success' => true,
                    'message' => 'Status changed Successfully!',
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Something went Wrong!',
                ];
            }
        } elseif ($type == 'linkedin') {
            $linkedin = Linkedin::find($id);
            if ($linkedin) {
                $linkedin->schedule_status = $status == 1 ? 'active' : 'inactive';
                $linkedin->save();
                $response = [
                    'success' => true,
                    'message' => 'Status changed Successfully!',
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Something went Wrong!',
                ];
            }
        } elseif ($type == 'youtube') {
            $youtube = Youtube::find($id);
            if ($youtube) {
                $youtube->schedule_status = $status == 1 ? 'active' : 'inactive';
                $youtube->save();
                $response = [
                    'success' => true,
                    'message' => 'Status changed Successfully!',
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Something went Wrong!',
                ];
            }
        }

        return response()->json($response);
    }

    /**
     * Summary of processPost
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function processPost(Request $request)
    {
        $action = $request->get('action');
        $link = $request->link;
        if ($link) { // link post
            if ($action == 'publish') {
                $response = $this->publishLink($request);
            }
            if ($action == 'queue') {
                $response = $this->queueLink($request);
            }
            if ($action == 'schedule') {
                $response = $this->scheduleLink($request);
            }
            if ($action == 'draft') {
                $response = $this->draftLink($request);
            }
        } else { // no link
            if ($action == 'publish') {
                $response = $this->publishPost($request);
            }
            if ($action == 'queue') {
                $response = $this->queuePost($request);
            }
            if ($action == 'schedule') {
                $response = $this->schedulePost($request);
            }
            if ($action == 'draft') {
                $response = $this->draftPost($request);
            }
        }

        return response()->json($response);
    }

    public function processChainPosts(Request $request)
    {
        try {
            $user = User::with('boards.pinterest', 'pages.facebook')->find(Auth::guard('user')->id());
            if (! $user) {
                return response()->json(['success' => false, 'message' => 'Unauthorized.']);
            }

            $accounts = $this->resolveAccountsForPost($request, $user);
            if ($accounts->isEmpty()) {
                return response()->json(['success' => false, 'message' => 'Please select at least one channel.']);
            }

            $rawFiles = $request->file('files');
            if ($rawFiles === null) {
                return response()->json(['success' => false, 'message' => 'Please upload at least one file for chain posts.']);
            }
            $files = is_array($rawFiles) ? array_values(array_filter($rawFiles)) : [$rawFiles];
            if (count($files) === 0) {
                return response()->json(['success' => false, 'message' => 'Please upload at least one file for chain posts.']);
            }

            $queueValidation = $this->validateQueueAccountsHaveTimeslots($accounts);
            if ($queueValidation !== null) {
                return response()->json($queueValidation);
            }

            $postsPerRound = max(1, (int) $request->input('chain_posts_per_round', 1));

            $postsToCreate = $this->countChainQueuePosts($accounts, $files, $request, $postsPerRound);
            if ($postsToCreate > 0) {
                $limitCheck = $this->checkScheduledPostsLimit($user, $postsToCreate);
                if (! $limitCheck['allowed']) {
                    return response()->json(['success' => false, 'message' => $limitCheck['message']]);
                }
            }

            $fileIndex = 0;
            $n = count($files);
            $facebookTokenOk = [];

            while ($fileIndex < $n) {
                foreach ($accounts as $account) {
                    if (count($account->timeslots) === 0) {
                        continue;
                    }
                    for ($r = 0; $r < $postsPerRound && $fileIndex < $n; $r++) {
                        $uploaded = $files[$fileIndex];
                        if (! $uploaded instanceof UploadedFile || ! $uploaded->isValid()) {
                            return response()->json(['success' => false, 'message' => 'One or more uploads are invalid.']);
                        }
                        $this->queueChainUploadedFileForAccount($user, $account, $request, $uploaded, $facebookTokenOk);
                        $fileIndex++;
                    }
                }
            }

            sleep(1);

            return response()->json([
                'success' => true,
                'message' => 'Your chain posts are queued!',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function countChainQueuePosts($accounts, array $files, Request $request, int $postsPerRound): int
    {
        $total = 0;
        $fileIndex = 0;
        $n = count($files);
        while ($fileIndex < $n) {
            foreach ($accounts as $account) {
                if (count($account->timeslots) === 0) {
                    continue;
                }
                for ($r = 0; $r < $postsPerRound && $fileIndex < $n; $r++) {
                    $total += $this->countQueuePostsForChainFileAndAccount($account, $files[$fileIndex], $request);
                    $fileIndex++;
                }
            }
        }

        return $total;
    }

    private function countQueuePostsForChainFileAndAccount($account, UploadedFile $file, Request $request): int
    {
        if ($account->type === 'instagram') {
            $ext = strtolower((string) ($file->getClientOriginalExtension() ?: ''));
            $isVideo = in_array($ext, ['mp4', 'mkv', 'mov', 'mpeg', 'webm'], true);
            $stub = [
                'has_files' => true,
                'image' => $isVideo ? null : 'stub',
                'video' => $isVideo ? 'stub' : null,
                'instagram_carousel_items' => [],
            ];

            return count($this->instagramContentFormatsFromRequest($request, $stub));
        }
        if ($account->type === 'facebook' || $account->type === 'pinterest' || $account->type === 'tiktok' || $account->type === 'linkedin' || $account->type === 'youtube') {
            return 1;
        }

        return 0;
    }

    private function queueChainUploadedFileForAccount(User $user, $account, Request $request, UploadedFile $file, array &$facebookTokenOk): void
    {
        $ext = strtolower($file->getClientOriginalExtension() ?: '');
        $isVideo = in_array($ext, ['mp4', 'mkv', 'mov', 'mpeg', 'webm'], true);
        $isDocument = in_array($ext, ['pdf', 'doc', 'docx', 'ppt', 'pptx'], true);
        $image = null;
        $video = null;
        $document = null;
        $documentName = null;
        if ($isVideo) {
            $video = saveToS3($file);
        } elseif ($isDocument) {
            $document = saveToS3($file);
            $documentName = (string) ($file->getClientOriginalName() ?: 'document.' . $ext);
        } else {
            $image = saveImage($file);
        }

        $content = $request->get('content');
        $comment = $request->get('comment');

        if ($account->type === 'facebook') {
            if (! isset($facebookTokenOk[$account->id])) {
                Facebook::where('id', $account->fb_id)->firstOrFail();
                $tokenResponse = FacebookService::validateToken($account);
                if (! $tokenResponse['success']) {
                    throw new Exception($tokenResponse['message'] ?? 'Failed to validate Facebook access token.');
                }
                $facebookTokenOk[$account->id] = true;
            }

            $nextTime = (new Post)->nextScheduleTime(
                ['account_id' => $account->id, 'social_type' => 'facebook', 'source' => 'schedule'],
                $account->timeslots,
                $user
            );
            $type = ! empty($image) ? 'photo' : 'video';
            $data = [
                'user_id' => $user->id,
                'account_id' => $account->id,
                'social_type' => 'facebook',
                'type' => $type,
                'source' => $this->source,
                'title' => $content,
                'comment' => $comment,
                'image' => $image,
                'video' => $video,
                'status' => 0,
                'publish_date' => $nextTime,
            ];
            $post = PostService::create($data);
            if ($this->verifyPostAccountBelongsToUser($post, $user)) {
                $user->incrementFeatureUsage('scheduled_posts_per_account', 1);
            }
            $this->logService->logQueuedPost('facebook', $post->id, ['type' => $type, 'publish_date' => $nextTime]);

            return;
        }

        if ($account->type === 'pinterest') {
            Pinterest::where('id', $account->pin_id)->firstOrFail();
            $nextTime = (new Post)->nextScheduleTime(
                ['account_id' => $account->id, 'social_type' => 'pinterest', 'source' => 'schedule'],
                $account->timeslots,
                $user
            );
            $type = ! empty($image) ? 'photo' : 'video';
            $data = [
                'user_id' => $user->id,
                'account_id' => $account->id,
                'social_type' => 'pinterest',
                'type' => $type,
                'source' => $this->source,
                'title' => $content,
                'comment' => $comment,
                'image' => $image,
                'video' => $video,
                'status' => 0,
                'publish_date' => $nextTime,
            ];
            $post = PostService::create($data);
            if ($this->verifyPostAccountBelongsToUser($post, $user)) {
                $user->incrementFeatureUsage('scheduled_posts_per_account', 1);
            }
            $this->logService->logQueuedPost('pinterest', $post->id, ['type' => $type, 'publish_date' => $nextTime]);

            return;
        }

        if ($account->type === 'tiktok') {
            Tiktok::where('id', $account->id)->firstOrFail();
            $nextTime = (new Post)->nextScheduleTime(
                ['account_id' => $account->id, 'social_type' => 'tiktok', 'source' => 'schedule'],
                $account->timeslots,
                $user
            );
            $type = ! empty($image) ? 'photo' : 'video';
            $data = [
                'user_id' => $user->id,
                'account_id' => $account->id,
                'social_type' => 'tiktok',
                'type' => $type,
                'source' => $this->source,
                'title' => $content,
                'comment' => $comment,
                'image' => $image,
                'video' => $video,
                'status' => 0,
                'publish_date' => $nextTime,
            ];
            $post = PostService::create($data);
            if ($this->verifyPostAccountBelongsToUser($post, $user)) {
                $user->incrementFeatureUsage('scheduled_posts_per_account', 1);
            }
            if ($type === 'photo' && (int) $request->get('tiktok_auto_add_music', 0)) {
                $post->update([
                    'metadata' => json_encode(['auto_add_music' => true]),
                ]);
            }
            $this->logService->logQueuedPost('tiktok', $post->id, ['type' => $type, 'publish_date' => $nextTime]);

            return;
        }

        if ($account->type === 'instagram') {
            $uploadPayload = [
                'error' => null,
                'has_files' => true,
                'image' => $image,
                'images' => $image ? [$image] : [],
                'video' => $video,
                'instagram_carousel_items' => [],
            ];
            $formats = $this->instagramContentFormatsFromRequest($request, $uploadPayload);
            if ($formats === []) {
                throw new Exception('Selected Instagram formats are not valid for the uploaded media.');
            }
            foreach ($formats as $fmt) {
                $igRequest = $request->duplicate(null, array_merge($request->all(), ['instagram_content_format' => $fmt]));
                $override = $fmt === 'carousel' ? null : $fmt;
                $nextTime = (new Post)->nextScheduleTime(
                    ['account_id' => $account->id, 'social_type' => 'instagram', 'source' => 'schedule'],
                    $account->timeslots,
                    $user
                );
                $created = $this->createInstagramPostFromCompose($user, $account, $igRequest, $uploadPayload, $nextTime, 0, $override);
                if ($created['error'] !== null) {
                    throw new Exception($created['error']);
                }
                $this->logService->logQueuedPost('instagram', $created['post']->id, ['type' => $created['plan']['type'], 'publish_date' => $nextTime]);
            }

            return;
        }

        if ($account->type === 'linkedin') {
            Linkedin::where('id', $account->id)->firstOrFail();
            $nextTime = (new Post)->nextScheduleTime(
                ['account_id' => $account->id, 'social_type' => 'linkedin', 'source' => 'schedule'],
                $account->timeslots,
                $user
            );
            $liUpload = [
                'image' => $image,
                'video' => $video,
                'document' => $document,
                'document_name' => $documentName,
                'instagram_carousel_items' => [],
            ];
            $plan = $this->linkedinComposePlan($request, $liUpload);
            if (! ($plan['success'] ?? false)) {
                throw new Exception((string) ($plan['message'] ?? 'Invalid LinkedIn post format.'));
            }
            $type = (string) $plan['type'];
            $data = [
                'user_id' => $user->id,
                'account_id' => $account->id,
                'social_type' => 'linkedin',
                'type' => $type,
                'source' => $this->source,
                'title' => $content,
                'comment' => $comment,
                'image' => $plan['image'],
                'video' => $plan['video'],
                'status' => 0,
                'publish_date' => $nextTime,
            ];
            if (! empty($plan['metadata'])) {
                $data['metadata'] = $plan['metadata'];
            }
            $post = PostService::create($data);
            if ($this->verifyPostAccountBelongsToUser($post, $user)) {
                $user->incrementFeatureUsage('scheduled_posts_per_account', 1);
            }
            $this->logService->logQueuedPost('linkedin', $post->id, ['type' => $type, 'publish_date' => $nextTime]);

            return;
        }

        if ($account->type === 'youtube') {
            if (empty($video)) {
                throw new Exception('YouTube publishing requires a video file.');
            }

            Youtube::where('id', $account->id)->firstOrFail();
            $nextTime = (new Post)->nextScheduleTime(
                ['account_id' => $account->id, 'social_type' => 'youtube', 'source' => 'schedule'],
                $account->timeslots,
                $user
            );
            $data = [
                'user_id' => $user->id,
                'account_id' => $account->id,
                'social_type' => 'youtube',
                'type' => 'video',
                'source' => $this->source,
                'title' => $content,
                'comment' => $comment,
                'video' => $video,
                'status' => 0,
                'publish_date' => $nextTime,
                'metadata' => json_encode($this->youtubeMetadataFromRequest($request)),
            ];
            $post = PostService::create($data);
            if ($this->verifyPostAccountBelongsToUser($post, $user)) {
                $user->incrementFeatureUsage('scheduled_posts_per_account', 1);
            }
            $this->logService->logQueuedPost('youtube', $post->id, ['type' => 'video', 'publish_date' => $nextTime]);

            return;
        }
    }

    /**
     * Resolve which accounts to use for post creation.
     * When account_ids are provided in the request (from create post modal selection),
     * use those. Otherwise use getScheduledActiveAccounts().
     *
     * @return \Illuminate\Support\Collection
     */
    private function resolveAccountsForPost(Request $request, User $user)
    {
        $accountIds = $request->get('account_ids');
        if ($accountIds) {
            if (is_string($accountIds)) {
                $accountIds = json_decode($accountIds, true);
            }
            if (is_array($accountIds) && count($accountIds) > 0) {
                return $user->getAccountsForPostCreation($accountIds);
            }
        }

        return $user->getScheduledActiveAccounts();
    }

    /**
     * Human-readable account name for queue validation errors.
     */
    private function accountLabelForQueueError(object $account): string
    {
        foreach (['name', 'display_name', 'username'] as $prop) {
            if (! empty($account->{$prop})) {
                return (string) $account->{$prop};
            }
        }

        return 'this account';
    }

    /**
     * Queue (add to posting hours) requires at least one timeslot per selected account.
     *
     * @param  \Illuminate\Support\Collection|\Traversable  $accounts
     * @return array|null Error payload for JSON, or null if OK
     */
    private function validateQueueAccountsHaveTimeslots($accounts): ?array
    {
        foreach ($accounts as $account) {
            if (count($account->timeslots) === 0) {
                $label = $this->accountLabelForQueueError($account);

                return [
                    'success' => false,
                    'message' => 'Please select at least one posting hour for ' . $label . '.',
                ];
            }
        }

        return null;
    }

    /**
     * Queue tab: image URL only when media exists in DB. Skips the Post model image accessor placeholder for text-only posts.
     */
    private function queueTimelinePostImageUrl(Post $post): ?string
    {
        $rawImage = $post->getAttributes()['image'] ?? null;
        if ($rawImage !== null && $rawImage !== '') {
            $rawImage = (string) $rawImage;
            if (str_starts_with($rawImage, 'http://') || str_starts_with($rawImage, 'https://')) {
                return $rawImage;
            }

            $type = strtolower((string) ($post->getAttributes()['type'] ?? ''));
            $social = strtolower((string) ($post->getAttributes()['social_type'] ?? ''));
            if (str_contains($social, 'tiktok') && $type === 'video') {
                return fetchFromS3($rawImage);
            }

            return url(getImage('', $rawImage));
        }

        $type = strtolower((string) ($post->getAttributes()['type'] ?? ''));
        $social = strtolower((string) ($post->getAttributes()['social_type'] ?? ''));
        if ((str_contains($social, 'instagram') || str_contains($social, 'thread')) && $type === 'carousel') {
            $fromCarousel = $this->instagramCarouselFirstStillPreviewUrl($post);

            return ($fromCarousel !== null && $fromCarousel !== '') ? $fromCarousel : null;
        }

        return null;
    }

    /**
     * First image URL from carousel metadata (ig_carousel / threads_carousel).
     */
    private function instagramCarouselFirstStillPreviewUrl(Post $post): ?string
    {
        $raw = $post->getAttributes()['metadata'] ?? null;
        if ($raw === null || $raw === '') {
            return null;
        }
        $meta = is_string($raw) ? json_decode($raw, true) : $raw;
        if (! is_array($meta)) {
            return null;
        }
        $items = $meta['ig_carousel'] ?? $meta['threads_carousel'] ?? [];
        if (! is_array($items)) {
            return null;
        }
        foreach ($items as $it) {
            if (! is_array($it) || empty($it['image'])) {
                continue;
            }
            $img = (string) $it['image'];
            if (str_starts_with($img, 'http://') || str_starts_with($img, 'https://')) {
                return $img;
            }

            return url(getImage('', $img));
        }

        return null;
    }

    /**
     * Public URL for a single stored path (image via getImage, video via S3).
     */
    private function resolveInstagramCarouselStoredPathUrl(string $path, bool $isVideo): string
    {
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }
        if ($isVideo) {
            return fetchFromS3($path);
        }

        return url(getImage('', $path));
    }

    /**
     * All carousel slides for queue/sent UI (images + videos from metadata ig_carousel/threads_carousel).
     *
     * @return list<array{type: string, url: string}>
     */
    private function instagramCarouselGalleryItemsFromPost(Post $post): array
    {
        $type = strtolower((string) ($post->getAttributes()['type'] ?? ''));
        if ($type !== 'carousel') {
            return [];
        }
        $raw = $post->getAttributes()['metadata'] ?? null;
        if ($raw === null || $raw === '') {
            return [];
        }
        $meta = is_string($raw) ? json_decode($raw, true) : $raw;
        if (! is_array($meta)) {
            return [];
        }
        $items = $meta['ig_carousel'] ?? $meta['threads_carousel'] ?? [];
        if (! is_array($items)) {
            return [];
        }
        $out = [];
        foreach ($items as $it) {
            if (! is_array($it)) {
                continue;
            }
            $img = isset($it['image']) ? trim((string) $it['image']) : '';
            $vid = isset($it['video']) ? trim((string) $it['video']) : '';
            if ($img !== '') {
                $out[] = [
                    'type' => 'image',
                    'url' => $this->resolveInstagramCarouselStoredPathUrl($img, false),
                ];
            } elseif ($vid !== '') {
                $out[] = [
                    'type' => 'video',
                    'url' => $this->resolveInstagramCarouselStoredPathUrl($vid, true),
                ];
            }
        }

        return $out;
    }

    /**
     * Schedule queue timeslots for an account.
     *
     * @return array<int, string> Timeslot strings (e.g. "09:00")
     */
    private function queueScheduleTimeslotStringsForAccount(User $user, int $accountId, string $accountType): array
    {
        $accountType = strtolower((string) $accountType);

        if ($accountType === 'instagram') {
            $ig = InstagramAccount::query()->find($accountId);
            if (! $ig) {
                return [];
            }

            return $ig->timeslots->pluck('timeslot')->sort()->values()->toArray();
        }

        return Timeslot::where('account_id', $accountId)
            ->where('account_type', $accountType)
            ->where('type', 'schedule')
            ->pluck('timeslot')
            ->sort()
            ->values()
            ->toArray();
    }

    /**
     * Public URL for a post's stored video file (S3 key or absolute URL).
     */
    private function postStoredVideoUrl(Post $post): ?string
    {
        $rawVideo = $post->getAttributes()['video'] ?? null;
        if ($rawVideo === null || $rawVideo === '') {
            return null;
        }
        $rawVideo = (string) $rawVideo;
        if (str_starts_with($rawVideo, 'http://') || str_starts_with($rawVideo, 'https://')) {
            return $rawVideo;
        }

        return fetchFromS3($rawVideo);
    }

    /**
     * Facebook media formats from create-post modal.
     * Input (from JS): facebook_content_formats = JSON array of "post" | "reel" | "story"
     * - image: post -> photo, story -> story
     * - video: post -> video, reel -> reel, story -> story
     *
     * Returns an array of concrete post types: ["photo","story"] etc.
     */
    private function facebookMediaTypesFromRequest(Request $request, bool $hasImage): array
    {
        $raw = $request->input('facebook_content_formats');
        $selected = [];
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $selected = $decoded;
            }
        } elseif (is_array($raw)) {
            $selected = $raw;
        }

        // Fallback to a single 'post' if nothing is selected.
        if (empty($selected)) {
            $selected = ['post'];
        }

        $selected = array_values(array_unique(array_map('strval', $selected)));
        $types = [];
        foreach ($selected as $format) {
            $format = strtolower(trim($format));
            if ($format === 'story') {
                $types[] = 'story';

                continue;
            }
            if ($format === 'reel') {
                // Only valid for video posts
                if (! $hasImage) {
                    $types[] = 'reel';
                }

                continue;
            }
            // 'post' fallback
            if ($hasImage) {
                $types[] = 'photo';
            } else {
                $types[] = 'video';
            }
        }

        // Ensure at least one sane default
        if (empty($types)) {
            $types[] = $hasImage ? 'photo' : 'video';
        }

        return array_values(array_unique($types));
    }

    /**
     * Publish Post
     *
     * @param  Request  $request
     */
    private function publishPost($request): array
    {
        try {
            $user = User::with('boards.pinterest', 'pages.facebook', 'linkedins')->findOrFail(Auth::guard('user')->id());
            $accounts = $this->resolveAccountsForPost($request, $user);
            $content = $request->get('content') ?? null;
            $comment = $request->get('comment') ?? null;
            $upload = $this->normalizeCreatePostFileUploads($request);
            if ($upload['error'] !== null) {
                return $upload['error'];
            }
            $file = $upload['has_files'];
            $image = $upload['image'];
            $video = $upload['video'];
            // Count total posts to be created (one per selected format per Facebook account)
            $totalPostsToCreate = 0;
            foreach ($accounts as $account) {
                if ($account->type == 'facebook') {
                    if ($file) {
                        $types = $this->facebookMediaTypesFromRequest($request, ! empty($image));
                        $totalPostsToCreate += count($types);
                    } else {
                        $totalPostsToCreate++;
                    }
                } elseif ($account->type == 'pinterest' && $file) {
                    $totalPostsToCreate++;
                } elseif ($account->type == 'tiktok' && $file) {
                    $totalPostsToCreate++;
                } elseif ($account->type === 'instagram' && $file) {
                    $totalPostsToCreate += count($this->instagramContentFormatsFromRequest($request, $upload));
                } elseif ($account->type === 'linkedin') {
                    $totalPostsToCreate++;
                } elseif ($account->type === 'youtube' && ! empty($video)) {
                    $totalPostsToCreate++;
                }
            }

            // Check scheduled posts limit before creating any posts
            /** @var User $user */
            $limitCheck = $this->checkScheduledPostsLimit($user, $totalPostsToCreate);
            if (! $limitCheck['allowed']) {
                return [
                    'success' => false,
                    'message' => $limitCheck['message'],
                ];
            }

            $publishDateNow = Carbon::now(TimezoneService::getUserTimezone($user))->format('Y-m-d H:i');

            foreach ($accounts as $account) {
                if ($account->type == 'facebook') {

                    Facebook::where('id', $account->fb_id)->firstOrFail();

                    // Validate token once per account
                    $tokenResponse = FacebookService::validateToken($account);
                    if (! $tokenResponse['success']) {
                        return [
                            'success' => false,
                            'message' => $tokenResponse['message'] ?? 'Failed to validate Facebook access token.',
                        ];
                    }
                    $access_token = $tokenResponse['access_token'];

                    if ($file) {
                        $types = $this->facebookMediaTypesFromRequest($request, ! empty($image));
                    } else {
                        $types = ['content_only'];
                    }

                    foreach ($types as $type) {
                        $data = [
                            'user_id' => $user->id,
                            'account_id' => $account->id,
                            'social_type' => 'facebook',
                            'type' => $type,
                            'source' => $this->source,
                            'title' => $content,
                            'comment' => $comment,
                            'image' => $image,
                            'video' => $video,
                            'status' => 0,
                            'publish_date' => $publishDateNow,
                        ];
                        $post = PostService::create($data);

                        if ($this->verifyPostAccountBelongsToUser($post, $user)) {
                            $user->incrementFeatureUsage('scheduled_posts_per_account', 1);
                        }

                        $this->logService->logPost('facebook', $type, $post->id, ['action' => 'publish'], 'pending');

                        $postData = PostService::postTypeBody($post);
                        PublishFacebookPost::dispatch($post->id, $postData, $access_token, $type, $post->comment);
                    }
                }
                if ($account->type == 'pinterest') {
                    $pinterest = Pinterest::where('id', $account->pin_id)->firstOrFail();
                    if ($file) {
                        // store in db
                        $type = ! empty($image) ? 'photo' : 'video';
                        $data = [
                            'user_id' => $user->id,
                            'account_id' => $account->id,
                            'social_type' => 'pinterest',
                            'type' => $type,
                            'source' => $this->source,
                            'title' => $content,
                            'comment' => $comment,
                            'image' => $image,
                            'video' => $video,
                            'status' => 0,
                            'publish_date' => $publishDateNow,
                        ];
                        $post = PostService::create($data);

                        // Verify account belongs to user before incrementing
                        if ($this->verifyPostAccountBelongsToUser($post, $user)) {
                            $user->incrementFeatureUsage('scheduled_posts_per_account', 1);
                        }

                        // Use validateToken for proper error handling
                        $tokenResponse = PinterestService::validateToken($account);
                        if (! $tokenResponse['success']) {
                            return [
                                'success' => false,
                                'message' => $tokenResponse['message'] ?? 'Failed to validate Pinterest access token.',
                            ];
                        }
                        $access_token = $tokenResponse['access_token'];
                        $postData = PostService::postTypeBody($post);
                        PublishPinterestPost::dispatch($post->id, $postData, $access_token, $type);
                    }
                }
                if ($account->type == 'tiktok') {
                    $tiktok = Tiktok::where('id', $account->id)->firstOrFail();

                    // Check if this is a TikTok-specific post (from modal)
                    $tiktokAccountId = $request->get('tiktok_account_id');
                    if ($tiktokAccountId && $tiktokAccountId == $account->id && $file) {
                        // Validate token and query latest creator info (required by TikTok Direct Post UX rules)
                        $tokenResponse = TikTokService::validateToken($account);
                        if (! $tokenResponse['success']) {
                            return [
                                'success' => false,
                                'message' => $tokenResponse['message'] ?? 'Failed to validate TikTok access token.',
                            ];
                        }
                        $access_token = $tokenResponse['access_token'];

                        $creatorInfoResponse = $this->tiktokService->queryCreatorInfo($access_token);
                        if (! $creatorInfoResponse['success']) {
                            return [
                                'success' => false,
                                'message' => $creatorInfoResponse['message'] ?? 'Failed to fetch TikTok creator info.',
                            ];
                        }
                        $creatorInfo = $creatorInfoResponse['data'] ?? [];
                        $canPost = $creatorInfo['can_post'] ?? ($creatorInfo['can_post_content'] ?? true);
                        if (! $canPost) {
                            return [
                                'success' => false,
                                'message' => $creatorInfo['can_not_post_reason'] ?? 'TikTok cannot accept new posts for this account right now. Please try again later.',
                            ];
                        }

                        $privacyLevel = (string) $request->get('tiktok_privacy_level');
                        $privacyOptions = $creatorInfo['privacy_level_options'] ?? [];
                        if (! is_array($privacyOptions) || empty($privacyOptions)) {
                            $privacyOptions = ['FOLLOWER_OF_CREATOR', 'MUTUAL_FOLLOW_FRIENDS', 'SELF_ONLY'];
                        }
                        if ($privacyLevel === '' || ! in_array($privacyLevel, $privacyOptions, true)) {
                            return [
                                'success' => false,
                                'message' => 'Invalid TikTok privacy option.',
                            ];
                        }
                        $commercialToggle = (int) $request->get('tiktok_commercial_toggle', 0);
                        if ($commercialToggle && $privacyLevel === 'SELF_ONLY') {
                            return [
                                'success' => false,
                                'message' => 'Only You is not available when disclose post content is enabled.',
                            ];
                        }

                        $isVideoPost = empty($image);
                        $maxVideoDuration = isset($creatorInfo['max_video_post_duration_sec']) ? (int) $creatorInfo['max_video_post_duration_sec'] : null;
                        $videoDuration = (float) $request->get('tiktok_video_duration', 0);
                        if ($isVideoPost && $maxVideoDuration && $videoDuration > 0 && $videoDuration > $maxVideoDuration) {
                            return [
                                'success' => false,
                                'message' => "Video duration exceeds TikTok account limit ({$maxVideoDuration}s).",
                            ];
                        }

                        // This is a TikTok post from the modal - use TikTok-specific fields
                        $type = ! empty($image) ? 'photo' : 'video';
                        $data = [
                            'user_id' => $user->id,
                            'account_id' => $account->id,
                            'social_type' => 'tiktok',
                            'type' => $type,
                            'source' => $this->source,
                            'title' => $request->get('content') ?: $content,
                            'comment' => $comment,
                            'image' => $image,
                            'video' => $video,
                            'status' => 0,
                            'publish_date' => $publishDateNow,
                        ];
                        $post = PostService::create($data);

                        // Store TikTok-specific metadata in metadata field
                        $tiktokMetadata = [
                            'privacy_level' => $request->get('tiktok_privacy_level'),
                            'disable_comment' => ! $request->get('tiktok_allow_comment', 0),
                            'disable_duet' => ! $request->get('tiktok_allow_duet', 0),
                            'disable_stitch' => ! $request->get('tiktok_allow_stitch', 0),
                            'commercial_content_toggle' => $request->get('tiktok_commercial_toggle', 0),
                            'your_brand' => $request->get('tiktok_your_brand', 0),
                            'branded_content' => $request->get('tiktok_branded_content', 0),
                        ];
                        if ($type === 'photo' && $request->get('tiktok_auto_add_music')) {
                            $tiktokMetadata['auto_add_music'] = true;
                        }
                        $post->update(['metadata' => json_encode($tiktokMetadata)]);

                        // Verify account belongs to user before incrementing
                        if ($this->verifyPostAccountBelongsToUser($post, $user)) {
                            $user->incrementFeatureUsage('scheduled_posts_per_account', 1);
                        }

                        $postData = PostService::postTypeBody($post);
                        // Merge TikTok metadata into postData
                        $postData = array_merge($postData, $tiktokMetadata);
                        PublishTikTokPost::dispatch($post->id, $postData, $access_token, $type);
                    } elseif ($file) {
                        // Legacy TikTok post (non-modal) - keep existing behavior
                        $type = ! empty($image) ? 'photo' : 'video';
                        $data = [
                            'user_id' => $user->id,
                            'account_id' => $account->id,
                            'social_type' => 'tiktok',
                            'type' => $type,
                            'source' => $this->source,
                            'title' => $content,
                            'comment' => $comment,
                            'image' => $image,
                            'video' => $video,
                            'status' => 0,
                            'publish_date' => $publishDateNow,
                        ];
                        $post = PostService::create($data);

                        // Verify account belongs to user before incrementing
                        if ($this->verifyPostAccountBelongsToUser($post, $user)) {
                            $user->incrementFeatureUsage('scheduled_posts_per_account', 1);
                        }

                        // Use validateToken for proper error handling
                        $tokenResponse = TikTokService::validateToken($account);
                        if (! $tokenResponse['success']) {
                            return [
                                'success' => false,
                                'message' => $tokenResponse['message'] ?? 'Failed to validate TikTok access token.',
                            ];
                        }
                        $access_token = $tokenResponse['access_token'];
                        $postData = PostService::postTypeBody($post);
                        PublishTikTokPost::dispatch($post->id, $postData, $access_token, $type);
                    }
                }
                if ($account->type === 'instagram') {
                    if (! $file) {
                        return [
                            'success' => false,
                            'message' => 'Instagram publishing requires an image or video.',
                        ];
                    }
                    $formats = $this->instagramContentFormatsFromRequest($request, $upload);
                    if ($formats === []) {
                        return [
                            'success' => false,
                            'message' => 'Selected Instagram formats are not valid for the uploaded media.',
                        ];
                    }
                    foreach ($formats as $fmt) {
                        $override = $fmt === 'carousel' ? null : $fmt;
                        $created = $this->createInstagramPostFromCompose($user, $account, $request, $upload, $publishDateNow, 0, $override);
                        if ($created['error'] !== null) {
                            return ['success' => false, 'message' => $created['error']];
                        }
                        $post = $created['post'];
                        $plan = $created['plan'];
                        $this->logService->logPost('instagram', $plan['type'], $post->id, ['action' => 'publish'], 'pending');
                        PublishInstagramPost::dispatch($post->id);
                    }
                }
                if ($account->type === 'threads') {
                    $created = $this->createThreadsPostFromCompose($user, $account, $request, $upload, $publishDateNow, 0);
                    if ($created['error'] !== null) {
                        return ['success' => false, 'message' => $created['error']];
                    }
                    $post = $created['post'];
                    $this->logService->logPost('threads', (string) ($created['plan']['type'] ?? 'content_only'), $post->id, ['action' => 'publish'], 'pending');
                    PublishThreadsPost::dispatch($post->id);
                }
                if ($account->type === 'linkedin') {
                    $plan = $this->linkedinComposePlan($request, $upload);
                    if (! ($plan['success'] ?? false)) {
                        return ['success' => false, 'message' => (string) ($plan['message'] ?? 'Invalid LinkedIn post format.')];
                    }
                    $type = (string) $plan['type'];
                    $data = [
                        'user_id' => $user->id,
                        'account_id' => $account->id,
                        'social_type' => 'linkedin',
                        'type' => $type,
                        'source' => $this->source,
                        'title' => $content,
                        'comment' => $comment,
                        'image' => $plan['image'],
                        'video' => $plan['video'],
                        'status' => 0,
                        'publish_date' => $publishDateNow,
                    ];
                    if (! empty($plan['metadata'])) {
                        $data['metadata'] = $plan['metadata'];
                    }
                    $post = PostService::create($data);
                    if ($this->verifyPostAccountBelongsToUser($post, $user)) {
                        $user->incrementFeatureUsage('scheduled_posts_per_account', 1);
                    }
                    $this->logService->logPost('linkedin', $type, $post->id, ['action' => 'publish'], 'pending');
                    PublishLinkedInPost::dispatch($post->id)->delay(now()->addSeconds(5));
                }
                if ($account->type === 'youtube') {
                    if (empty($video)) {
                        return [
                            'success' => false,
                            'message' => 'YouTube publishing requires a video file.',
                        ];
                    }

                    $tokenResponse = YouTubeService::validateToken($account);
                    if (! $tokenResponse['success']) {
                        return [
                            'success' => false,
                            'message' => $tokenResponse['message'] ?? 'Failed to validate YouTube access token.',
                        ];
                    }

                    $data = [
                        'user_id' => $user->id,
                        'account_id' => $account->id,
                        'social_type' => 'youtube',
                        'type' => 'video',
                        'source' => $this->source,
                        'title' => $content,
                        'comment' => $comment,
                        'video' => $video,
                        'status' => 0,
                        'publish_date' => $publishDateNow,
                        'metadata' => json_encode($this->youtubeMetadataFromRequest($request)),
                    ];
                    $post = PostService::create($data);
                    if ($this->verifyPostAccountBelongsToUser($post, $user)) {
                        $user->incrementFeatureUsage('scheduled_posts_per_account', 1);
                    }
                    $this->logService->logPost('youtube', 'video', $post->id, ['action' => 'publish'], 'pending');
                    PublishYouTubePost::dispatch($post->id)->delay(now()->addSeconds(5));
                }
            }
            $response = [
                'success' => true,
                'message' => 'Your posts are being Published!',
            ];
        } catch (Exception $e) {
            $response = [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
        sleep(1);

        return $response;
    }

    /**
     * Draft Post - Upload posts as drafts to draft-active platforms
     *
     * @param  Request  $request
     */
    private function draftPost($request): array
    {
        try {
            $user = User::with('boards.pinterest', 'pages.facebook', 'tiktok')->findOrFail(Auth::guard('user')->id());
            $accounts = $this->resolveAccountsForPost($request, $user);

            // Filter accounts for draft-active platforms only
            $draftActivePlatforms = DraftEnum::getDraftActivePlatformValues();
            $draftAccounts = $accounts->filter(function ($account) use ($draftActivePlatforms) {
                return in_array($account->type, $draftActivePlatforms);
            });

            if ($draftAccounts->isEmpty()) {
                return [
                    'success' => false,
                    'message' => 'No draft-active accounts found. Please connect a TikTok account to use draft functionality.',
                ];
            }

            $content = $request->get('content') ?? null;
            $comment = $request->get('comment') ?? null;
            $file = $request->file('files') ? true : false;
            $image = $video = null;

            if ($file) {
                $is_video = $request->video;
                if ($is_video) {
                    $video = saveToS3($request->file('files'));
                } else {
                    $image = saveImage($request->file('files'));
                }
            }

            // Count total posts to be created
            $totalPostsToCreate = count($draftAccounts);

            // Check scheduled posts limit before creating any posts
            /** @var User $user */
            $limitCheck = $this->checkScheduledPostsLimit($user, $totalPostsToCreate);
            if (! $limitCheck['allowed']) {
                return [
                    'success' => false,
                    'message' => $limitCheck['message'],
                ];
            }

            $publishDateNow = Carbon::now(TimezoneService::getUserTimezone($user))->format('Y-m-d H:i');

            foreach ($draftAccounts as $account) {
                // Handle TikTok draft posts
                if ($account->type == 'tiktok' && DraftEnum::isDraftActiveFor('tiktok')) {
                    if ($file) {
                        // Determine post type
                        $type = ! empty($image) ? 'photo' : 'video';

                        // Store in db
                        $data = [
                            'user_id' => $user->id,
                            'account_id' => $account->id,
                            'social_type' => 'tiktok',
                            'type' => $type,
                            'source' => $this->source,
                            'title' => $content,
                            'comment' => $comment,
                            'image' => $image,
                            'video' => $video,
                            'url' => $image, // For photo posts
                            'file_url' => $video, // For video posts
                            'status' => 0, // Draft status
                            'publish_date' => $publishDateNow,
                        ];
                        $post = PostService::create($data);

                        // Verify account belongs to user before incrementing
                        if ($this->verifyPostAccountBelongsToUser($post, $user)) {
                            $user->incrementFeatureUsage('scheduled_posts_per_account', 1);
                        }

                        // Log draft post creation
                        $this->logService->logDraft('tiktok', $type, $post->id, ['action' => 'draft'], 'pending');

                        // Use validateToken for proper error handling
                        $tokenResponse = TikTokService::validateToken($account);
                        if (! $tokenResponse['success']) {
                            $this->logService->logDraft('tiktok', $type, $post->id, ['action' => 'draft'], 'failed');

                            return [
                                'success' => false,
                                'message' => $tokenResponse['message'] ?? 'Failed to validate TikTok access token.',
                            ];
                        }
                        $access_token = $tokenResponse['access_token'];
                        $postData = PostService::postTypeBody($post);

                        // Dispatch draft upload
                        if ($type == 'video') {
                            $this->tiktokService->uploadVideoDraft($post->id, $postData, $access_token, 'PULL_FROM_URL');
                        } elseif ($type == 'photo') {
                            $this->tiktokService->uploadPhotoDraft($post->id, $postData, $access_token);
                        }
                    } else {
                        // No file provided for draft post
                        return [
                            'success' => false,
                            'message' => 'Draft posts require a file (image or video).',
                        ];
                    }
                }
            }

            $response = [
                'success' => true,
                'message' => 'Your posts are being uploaded as drafts!',
            ];
        } catch (Exception $e) {
            $response = [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
        sleep(1);

        return $response;
    }

    /**
     * Draft Link - Upload link posts as drafts to draft-active platforms
     *
     * @param  Request  $request
     */
    private function draftLink($request): array
    {
        try {
            $user = User::with('boards.pinterest', 'pages.facebook', 'tiktok')->findOrFail(Auth::guard('user')->id());
            $accounts = $this->resolveAccountsForPost($request, $user);

            // Filter accounts for draft-active platforms only
            $draftActivePlatforms = DraftEnum::getDraftActivePlatformValues();
            $draftAccounts = $accounts->filter(function ($account) use ($draftActivePlatforms) {
                return in_array($account->type, $draftActivePlatforms);
            });

            if ($draftAccounts->isEmpty()) {
                return [
                    'success' => false,
                    'message' => 'No draft-active accounts found. Please connect a TikTok account to use draft functionality.',
                ];
            }

            $content = $request->get('content') ?? null;
            $link = $request->get('link') ?? null;
            $comment = $request->get('comment') ?? null;
            $file = $request->file('files') ? true : false;
            $image = null;

            if ($file) {
                $image = saveImage($request->file('files'));
            }

            // Count total posts to be created
            $totalPostsToCreate = count($draftAccounts);

            // Check scheduled posts limit before creating any posts
            /** @var User $user */
            $limitCheck = $this->checkScheduledPostsLimit($user, $totalPostsToCreate);
            if (! $limitCheck['allowed']) {
                return [
                    'success' => false,
                    'message' => $limitCheck['message'],
                ];
            }

            $publishDateNow = Carbon::now(TimezoneService::getUserTimezone($user))->format('Y-m-d H:i');

            foreach ($draftAccounts as $account) {
                // Handle TikTok draft link posts
                if ($account->type == 'tiktok' && DraftEnum::isDraftActiveFor('tiktok')) {
                    if ($file && $image) {
                        // For TikTok, fetch title and thumbnail from link and convert to photo post
                        $linkInfo = $this->fetchTikTokLinkInfo($link);
                        if (! $linkInfo || empty($linkInfo['image'])) {
                            return [
                                'success' => false,
                                'message' => 'Failed to fetch title and thumbnail from the link. Please ensure the link is accessible.',
                            ];
                        }

                        // Store in db as photo post (not link)
                        $data = [
                            'user_id' => $user->id,
                            'account_id' => $account->id,
                            'social_type' => 'tiktok',
                            'type' => 'photo', // Changed from "link" to "photo"
                            'source' => $this->source,
                            'title' => ! empty($linkInfo['title']) ? $linkInfo['title'] : $content,
                            'comment' => $comment,
                            'url' => $linkInfo['image'], // Store thumbnail image URL
                            'image' => $linkInfo['image'], // Store thumbnail image URL
                            'link' => $link,
                            'status' => 0, // Draft status
                            'publish_date' => $publishDateNow,
                        ];
                        $post = PostService::create($data);

                        // Verify account belongs to user before incrementing
                        if ($this->verifyPostAccountBelongsToUser($post, $user)) {
                            $user->incrementFeatureUsage('scheduled_posts_per_account', 1);
                        }

                        // Use validateToken for proper error handling
                        $tokenResponse = TikTokService::validateToken($account);
                        if (! $tokenResponse['success']) {
                            return [
                                'success' => false,
                                'message' => $tokenResponse['message'] ?? 'Failed to validate TikTok access token.',
                            ];
                        }
                        $access_token = $tokenResponse['access_token'];
                        $postData = PostService::postTypeBody($post);

                        // Upload as photo draft
                        $this->tiktokService->uploadPhotoDraft($post->id, $postData, $access_token);
                    } else {
                        return [
                            'success' => false,
                            'message' => 'Draft link posts require an image file.',
                        ];
                    }
                }
                // Add other draft-active platforms here in the future
            }

            $response = [
                'success' => true,
                'message' => 'Your link posts are being uploaded as drafts!',
            ];
        } catch (Exception $e) {
            $response = [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
        sleep(1);

        return $response;
    }

    // queue post
    private function queuePost($request)
    {
        try {
            $user = User::with('boards.pinterest', 'pages.facebook')->find(Auth::guard('user')->id());
            $accounts = $this->resolveAccountsForPost($request, $user);
            $queueValidation = $this->validateQueueAccountsHaveTimeslots($accounts);
            if ($queueValidation !== null) {
                return $queueValidation;
            }
            $content = $request->get('content') ?? null;
            $comment = $request->get('comment') ?? null;
            $upload = $this->normalizeCreatePostFileUploads($request);
            if ($upload['error'] !== null) {
                return $upload['error'];
            }
            $file = $upload['has_files'];
            $image = $upload['image'];
            $video = $upload['video'];

            $postsToCreate = 0;
            foreach ($accounts as $account) {
                if (count($account->timeslots) > 0) {
                    if ($account->type == 'facebook') {
                        if ($file) {
                            $types = $this->facebookMediaTypesFromRequest($request, ! empty($image));
                            $postsToCreate += count($types);
                        } else {
                            $postsToCreate++;
                        }
                    } elseif ($account->type == 'pinterest' && $file) {
                        $postsToCreate++;
                    } elseif ($account->type == 'tiktok' && $file) {
                        $postsToCreate++;
                    } elseif ($account->type === 'instagram' && $file) {
                        $postsToCreate += count($this->instagramContentFormatsFromRequest($request, $upload));
                    } elseif ($account->type === 'linkedin') {
                        $postsToCreate++;
                    } elseif ($account->type === 'youtube' && ! empty($video)) {
                        $postsToCreate++;
                    }
                }
            }

            // Check scheduled posts limit before creating any posts
            if ($postsToCreate > 0) {
                /** @var User $user */
                $limitCheck = $this->checkScheduledPostsLimit($user, $postsToCreate);
                if (! $limitCheck['allowed']) {
                    return [
                        'success' => false,
                        'message' => $limitCheck['message'],
                    ];
                }
            }

            foreach ($accounts as $account) {
                if (count($account->timeslots) > 0) {
                    if ($account->type == 'facebook') {
                        Facebook::where('id', $account->fb_id)->firstOrFail();
                        $nextTime = (new Post)->nextScheduleTime(['account_id' => $account->id, 'social_type' => 'facebook', 'source' => 'schedule'], $account->timeslots, $user);
                        if ($file) {
                            $types = $this->facebookMediaTypesFromRequest($request, ! empty($image));
                        } else {
                            $types = ['content_only'];
                        }
                        foreach ($types as $type) {
                            $data = [
                                'user_id' => $user->id,
                                'account_id' => $account->id,
                                'social_type' => 'facebook',
                                'type' => $type,
                                'source' => $this->source,
                                'title' => $content,
                                'comment' => $comment,
                                'image' => $image,
                                'video' => $video,
                                'status' => 0,
                                'publish_date' => $nextTime,
                            ];
                            $post = PostService::create($data);
                            if ($this->verifyPostAccountBelongsToUser($post, $user)) {
                                $user->incrementFeatureUsage('scheduled_posts_per_account', 1);
                            }
                            $this->logService->logQueuedPost('facebook', $post->id, ['type' => $type, 'publish_date' => $nextTime]);
                        }
                    }
                    if ($account->type == 'pinterest') {
                        Pinterest::where('id', $account->pin_id)->firstOrFail();
                        if ($file) {
                            $nextTime = (new Post)->nextScheduleTime(['account_id' => $account->id, 'social_type' => 'pinterest', 'source' => 'schedule'], $account->timeslots, $user);
                            // store in db
                            $type = ! empty($image) ? 'photo' : 'video';
                            $data = [
                                'user_id' => $user->id,
                                'account_id' => $account->id,
                                'social_type' => 'pinterest',
                                'type' => $type,
                                'source' => $this->source,
                                'title' => $content,
                                'comment' => $comment,
                                'image' => $image,
                                'video' => $video,
                                'status' => 0,
                                'publish_date' => $nextTime,
                            ];
                            $post = PostService::create($data);
                            // Verify account belongs to user before incrementing
                            if ($this->verifyPostAccountBelongsToUser($post, $user)) {
                                $user->incrementFeatureUsage('scheduled_posts_per_account', 1);
                            }
                            // Log queued post
                            $this->logService->logQueuedPost('pinterest', $post->id, ['type' => $type, 'publish_date' => $nextTime]);
                        }
                    }
                    if ($account->type == 'tiktok') {
                        Tiktok::where('id', $account->id)->firstOrFail();
                        if ($file) {
                            $nextTime = (new Post)->nextScheduleTime(['account_id' => $account->id, 'social_type' => 'tiktok', 'source' => 'schedule'], $account->timeslots, $user);
                            // store in db
                            $type = ! empty($image) ? 'photo' : 'video';
                            $data = [
                                'user_id' => $user->id,
                                'account_id' => $account->id,
                                'social_type' => 'tiktok',
                                'type' => $type,
                                'source' => $this->source,
                                'title' => $content,
                                'comment' => $comment,
                                'image' => $image,
                                'video' => $video,
                                'status' => 0,
                                'publish_date' => $nextTime,
                            ];
                            $post = PostService::create($data);
                            // Verify account belongs to user before incrementing
                            if ($this->verifyPostAccountBelongsToUser($post, $user)) {
                                $user->incrementFeatureUsage('scheduled_posts_per_account', 1);
                            }
                            // Log queued post
                            $this->logService->logQueuedPost('tiktok', $post->id, ['type' => $type, 'publish_date' => $nextTime]);
                        }
                    }
                    if ($account->type === 'instagram') {
                        if (! $file) {
                            return [
                                'success' => false,
                                'message' => 'Instagram queue posts require an image or video.',
                            ];
                        }
                        $formats = $this->instagramContentFormatsFromRequest($request, $upload);
                        if ($formats === []) {
                            return [
                                'success' => false,
                                'message' => 'Selected Instagram formats are not valid for the uploaded media.',
                            ];
                        }
                        foreach ($formats as $fmt) {
                            $nextTime = (new Post)->nextScheduleTime(
                                ['account_id' => $account->id, 'social_type' => 'instagram', 'source' => 'schedule'],
                                $account->timeslots,
                                $user
                            );
                            $override = $fmt === 'carousel' ? null : $fmt;
                            $created = $this->createInstagramPostFromCompose($user, $account, $request, $upload, $nextTime, 0, $override);
                            if ($created['error'] !== null) {
                                return ['success' => false, 'message' => $created['error']];
                            }
                            $this->logService->logQueuedPost('instagram', $created['post']->id, ['type' => $created['plan']['type'], 'publish_date' => $nextTime]);
                        }
                    }
                    if ($account->type === 'threads') {
                        $nextTime = (new Post)->nextScheduleTime(
                            ['account_id' => $account->id, 'social_type' => 'threads', 'source' => 'schedule'],
                            $account->timeslots,
                            $user
                        );
                        $created = $this->createThreadsPostFromCompose($user, $account, $request, $upload, $nextTime, 0);
                        if ($created['error'] !== null) {
                            return ['success' => false, 'message' => $created['error']];
                        }
                        $this->logService->logQueuedPost('threads', $created['post']->id, ['type' => $created['plan']['type'] ?? 'content_only', 'publish_date' => $nextTime]);
                    }
                    if ($account->type === 'linkedin') {
                        Linkedin::where('id', $account->id)->firstOrFail();
                        $nextTime = (new Post)->nextScheduleTime(
                            ['account_id' => $account->id, 'social_type' => 'linkedin', 'source' => 'schedule'],
                            $account->timeslots,
                            $user
                        );
                        $plan = $this->linkedinComposePlan($request, $upload);
                        if (! ($plan['success'] ?? false)) {
                            return ['success' => false, 'message' => (string) ($plan['message'] ?? 'Invalid LinkedIn post format.')];
                        }
                        $type = (string) $plan['type'];
                        $data = [
                            'user_id' => $user->id,
                            'account_id' => $account->id,
                            'social_type' => 'linkedin',
                            'type' => $type,
                            'source' => $this->source,
                            'title' => $content,
                            'comment' => $comment,
                            'image' => $plan['image'],
                            'video' => $plan['video'],
                            'status' => 0,
                            'publish_date' => $nextTime,
                        ];
                        if (! empty($plan['metadata'])) {
                            $data['metadata'] = $plan['metadata'];
                        }
                        $post = PostService::create($data);
                        if ($this->verifyPostAccountBelongsToUser($post, $user)) {
                            $user->incrementFeatureUsage('scheduled_posts_per_account', 1);
                        }
                        $this->logService->logQueuedPost('linkedin', $post->id, ['type' => $type, 'publish_date' => $nextTime]);
                    }
                    if ($account->type === 'youtube') {
                        if (empty($video)) {
                            return [
                                'success' => false,
                                'message' => 'YouTube scheduling requires a video file.',
                            ];
                        }
                        Youtube::where('id', $account->id)->firstOrFail();
                        $nextTime = (new Post)->nextScheduleTime(
                            ['account_id' => $account->id, 'social_type' => 'youtube', 'source' => 'schedule'],
                            $account->timeslots,
                            $user
                        );
                        $data = [
                            'user_id' => $user->id,
                            'account_id' => $account->id,
                            'social_type' => 'youtube',
                            'type' => 'video',
                            'source' => $this->source,
                            'title' => $content,
                            'comment' => $comment,
                            'video' => $video,
                            'status' => 0,
                            'publish_date' => $nextTime,
                            'scheduled' => 1,
                            'metadata' => json_encode($this->youtubeMetadataFromRequest($request)),
                        ];
                        $post = PostService::create($data);
                        if ($this->verifyPostAccountBelongsToUser($post, $user)) {
                            $user->incrementFeatureUsage('scheduled_posts_per_account', 1);
                        }
                        $this->logService->logQueuedPost('youtube', $post->id, ['type' => 'video', 'publish_date' => $nextTime]);
                    }
                    $response = [
                        'success' => true,
                        'message' => 'Your posts are queued for Later!',
                    ];
                }
            }
        } catch (Exception $e) {
            $response = [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }

        return $response;
    }

    // schedule post
    private function schedulePost($request)
    {
        try {
            $user = User::with('boards.pinterest', 'pages.facebook')->find(Auth::guard('user')->id());
            $accounts = $this->resolveAccountsForPost($request, $user);
            $content = $request->get('content') ?? null;
            $comment = $request->get('comment') ?? null;
            $schedule_date = $request->schedule_date;
            $schedule_time = $request->schedule_time;
            $upload = $this->normalizeCreatePostFileUploads($request);
            if ($upload['error'] !== null) {
                return $upload['error'];
            }
            $file = $upload['has_files'];
            $image = $upload['image'];
            $video = $upload['video'];

            // Count how many posts will be created (one per selected format per Facebook account)
            $postsToCreate = 0;
            foreach ($accounts as $account) {
                if ($account->type == 'facebook') {
                    if ($file) {
                        $types = $this->facebookMediaTypesFromRequest($request, ! empty($image));
                        $postsToCreate += count($types);
                    } else {
                        $postsToCreate++;
                    }
                } elseif ($account->type == 'pinterest' && $file) {
                    $postsToCreate++;
                } elseif ($account->type == 'tiktok' && $file) {
                    $postsToCreate++;
                } elseif ($account->type === 'instagram' && $file) {
                    $postsToCreate += count($this->instagramContentFormatsFromRequest($request, $upload));
                } elseif ($account->type === 'linkedin') {
                    $postsToCreate++;
                } elseif ($account->type === 'youtube' && ! empty($video)) {
                    $postsToCreate++;
                }
            }

            // Check scheduled posts limit before creating any posts
            if ($postsToCreate > 0) {
                /** @var User $user */
                $limitCheck = $this->checkScheduledPostsLimit($user, $postsToCreate);
                if (! $limitCheck['allowed']) {
                    return [
                        'success' => false,
                        'message' => $limitCheck['message'],
                    ];
                }
            }

            foreach ($accounts as $account) {
                $scheduleDateTime = date('Y-m-d', strtotime($schedule_date)) . ' ' . date('H:i', strtotime($schedule_time));
                if ($account->type == 'facebook') {
                    Facebook::where('id', $account->fb_id)->firstOrFail();
                    if ($file) {
                        $types = $this->facebookMediaTypesFromRequest($request, ! empty($image));
                    } else {
                        $types = ['content_only'];
                    }
                    foreach ($types as $type) {
                        $data = [
                            'user_id' => $user->id,
                            'account_id' => $account->id,
                            'social_type' => 'facebook',
                            'type' => $type,
                            'source' => $this->source,
                            'title' => $content,
                            'comment' => $comment,
                            'image' => $image,
                            'video' => $video,
                            'status' => 0,
                            'publish_date' => $scheduleDateTime,
                            'scheduled' => 1,
                        ];
                        $post = PostService::create($data);
                        if ($this->verifyPostAccountBelongsToUser($post, $user)) {
                            $user->incrementFeatureUsage('scheduled_posts_per_account', 1);
                        }
                        $this->logService->logScheduledPost('facebook', $post->id, $scheduleDateTime, ['type' => $type]);
                    }
                }
                if ($account->type == 'pinterest') {
                    Pinterest::where('id', $account->pin_id)->firstOrFail();
                    if ($file) {
                        // store in db
                        $type = ! empty($image) ? 'photo' : 'video';
                        $data = [
                            'user_id' => $user->id,
                            'account_id' => $account->id,
                            'social_type' => 'pinterest',
                            'type' => $type,
                            'source' => $this->source,
                            'title' => $content,
                            'comment' => $comment,
                            'image' => $image,
                            'video' => $video,
                            'status' => 0,
                            'publish_date' => $scheduleDateTime,
                            'scheduled' => 1,
                        ];
                        $post = PostService::create($data);
                        // Verify account belongs to user before incrementing
                        if ($this->verifyPostAccountBelongsToUser($post, $user)) {
                            $user->incrementFeatureUsage('scheduled_posts_per_account', 1);
                        }
                        // Log scheduled post
                        $this->logService->logScheduledPost('pinterest', $post->id, $scheduleDateTime, ['type' => $type]);
                    }
                }
                if ($account->type == 'tiktok') {
                    Tiktok::where('id', $account->id)->firstOrFail();
                    if ($file) {
                        // store in db
                        $type = ! empty($image) ? 'photo' : 'video';
                        $data = [
                            'user_id' => $user->id,
                            'account_id' => $account->id,
                            'social_type' => 'tiktok',
                            'type' => $type,
                            'source' => $this->source,
                            'title' => $content,
                            'comment' => $comment,
                            'image' => $image,
                            'video' => $video,
                            'status' => 0,
                            'publish_date' => $scheduleDateTime,
                            'scheduled' => 1,
                        ];
                        $post = PostService::create($data);
                        // Verify account belongs to user before incrementing
                        if ($this->verifyPostAccountBelongsToUser($post, $user)) {
                            $user->incrementFeatureUsage('scheduled_posts_per_account', 1);
                        }
                        // Log scheduled post
                        $this->logService->logScheduledPost('tiktok', $post->id, $scheduleDateTime, ['type' => $type]);
                    }
                }
                if ($account->type === 'instagram') {
                    if (! $file) {
                        continue;
                    }
                    $formats = $this->instagramContentFormatsFromRequest($request, $upload);
                    if ($formats === []) {
                        return [
                            'success' => false,
                            'message' => 'Selected Instagram formats are not valid for the uploaded media.',
                        ];
                    }
                    foreach ($formats as $fmt) {
                        $override = $fmt === 'carousel' ? null : $fmt;
                        $created = $this->createInstagramPostFromCompose($user, $account, $request, $upload, $scheduleDateTime, 1, $override);
                        if ($created['error'] !== null) {
                            return ['success' => false, 'message' => $created['error']];
                        }
                        $this->logService->logScheduledPost('instagram', $created['post']->id, $scheduleDateTime, ['type' => $created['plan']['type']]);
                    }
                }
                if ($account->type === 'threads') {
                    $created = $this->createThreadsPostFromCompose($user, $account, $request, $upload, $scheduleDateTime, 1);
                    if ($created['error'] !== null) {
                        return ['success' => false, 'message' => $created['error']];
                    }
                    $this->logService->logScheduledPost('threads', $created['post']->id, $scheduleDateTime, ['type' => $created['plan']['type'] ?? 'content_only']);
                }
                if ($account->type === 'linkedin') {
                    Linkedin::where('id', $account->id)->firstOrFail();
                    $plan = $this->linkedinComposePlan($request, $upload);
                    if (! ($plan['success'] ?? false)) {
                        return ['success' => false, 'message' => (string) ($plan['message'] ?? 'Invalid LinkedIn post format.')];
                    }
                    $type = (string) $plan['type'];
                    $data = [
                        'user_id' => $user->id,
                        'account_id' => $account->id,
                        'social_type' => 'linkedin',
                        'type' => $type,
                        'source' => $this->source,
                        'title' => $content,
                        'comment' => $comment,
                        'image' => $plan['image'],
                        'video' => $plan['video'],
                        'status' => 0,
                        'publish_date' => $scheduleDateTime,
                        'scheduled' => 1,
                    ];
                    if (! empty($plan['metadata'])) {
                        $data['metadata'] = $plan['metadata'];
                    }
                    $post = PostService::create($data);
                    if ($this->verifyPostAccountBelongsToUser($post, $user)) {
                        $user->incrementFeatureUsage('scheduled_posts_per_account', 1);
                    }
                    $this->logService->logScheduledPost('linkedin', $post->id, $scheduleDateTime, ['type' => $type]);
                }
                if ($account->type === 'youtube') {
                    if (empty($video)) {
                        return [
                            'success' => false,
                            'message' => 'YouTube scheduling requires a video file.',
                        ];
                    }
                    Youtube::where('id', $account->id)->firstOrFail();
                    $data = [
                        'user_id' => $user->id,
                        'account_id' => $account->id,
                        'social_type' => 'youtube',
                        'type' => 'video',
                        'source' => $this->source,
                        'title' => $content,
                        'comment' => $comment,
                        'video' => $video,
                        'status' => 0,
                        'publish_date' => $scheduleDateTime,
                        'scheduled' => 1,
                        'metadata' => json_encode($this->youtubeMetadataFromRequest($request)),
                    ];
                    $post = PostService::create($data);
                    if ($this->verifyPostAccountBelongsToUser($post, $user)) {
                        $user->incrementFeatureUsage('scheduled_posts_per_account', 1);
                    }
                    $this->logService->logScheduledPost('youtube', $post->id, $scheduleDateTime, ['type' => 'video']);
                }
                $response = [
                    'success' => true,
                    'message' => 'Your posts are scheduled for Later!',
                ];
            }
        } catch (Exception $e) {
            $response = [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }

        return $response;
    }

    // publish link post
    private function publishLink($request)
    {
        try {
            $user = User::with('boards.pinterest', 'pages.facebook')->find(Auth::guard('user')->id());
            $accounts = $this->resolveAccountsForPost($request, $user);
            $content = $request->get('content') ?? null;
            $comment = $request->get('comment') ?? null;
            $url = $request->get('url') ?? null;
            $image = $request->get('image') ?? null;
            if (! empty($url)) {
                // Instagram link posts are not supported (publishing requires public media URLs).
                $totalPostsToCreate = 0;
                foreach ($accounts as $acc) {
                    if (($acc->type ?? '') !== 'instagram') {
                        $totalPostsToCreate++;
                    }
                }

                // Check scheduled posts limit before creating any posts
                /** @var User $user */
                $limitCheck = $this->checkScheduledPostsLimit($user, $totalPostsToCreate);
                if (! $limitCheck['allowed']) {
                    return [
                        'success' => false,
                        'message' => $limitCheck['message'],
                    ];
                }

                $publishDateNow = Carbon::now(TimezoneService::getUserTimezone($user))->format('Y-m-d H:i');

                foreach ($accounts as $account) {
                    if ($account->type == 'facebook') {
                        // store in db (image may be empty for Facebook/Instagram links)
                        $data = [
                            'user_id' => $user->id,
                            'account_id' => $account->id,
                            'social_type' => 'facebook',
                            'type' => 'link',
                            'source' => $this->source,
                            'title' => $content,
                            'comment' => $comment,
                            'url' => $url,
                            'image' => $image,
                            'status' => 0,
                            'publish_date' => $publishDateNow,
                        ];
                        $post = PostService::create($data);

                        // Verify account belongs to user before incrementing
                        if ($this->verifyPostAccountBelongsToUser($post, $user)) {
                            $user->incrementFeatureUsage('scheduled_posts_per_account', 1);
                        }

                        // Use validateToken for proper error handling
                        $tokenResponse = FacebookService::validateToken($account);
                        if (! $tokenResponse['success']) {
                            return [
                                'success' => false,
                                'message' => $tokenResponse['message'] ?? 'Failed to validate Facebook access token.',
                            ];
                        }
                        $access_token = $tokenResponse['access_token'];
                        $postData = PostService::postTypeBody($post);
                        PublishFacebookPost::dispatch($post->id, $postData, $access_token, 'link', $post->comment);
                    }
                    if ($account->type == 'pinterest') {
                        // store in db
                        $data = [
                            'user_id' => $user->id,
                            'account_id' => $account->id,
                            'social_type' => 'pinterest',
                            'type' => 'link',
                            'source' => $this->source,
                            'title' => $content,
                            'comment' => $comment,
                            'url' => $url,
                            'image' => $image,
                            'status' => 0,
                            'publish_date' => $publishDateNow,
                        ];
                        $post = PostService::create($data);

                        // Verify account belongs to user before incrementing
                        if ($this->verifyPostAccountBelongsToUser($post, $user)) {
                            $user->incrementFeatureUsage('scheduled_posts_per_account', 1);
                        }

                        // Use validateToken for proper error handling
                        $tokenResponse = PinterestService::validateToken($account);
                        if (! $tokenResponse['success']) {
                            return [
                                'success' => false,
                                'message' => $tokenResponse['message'] ?? 'Failed to validate Pinterest access token.',
                            ];
                        }
                        $access_token = $tokenResponse['access_token'];
                        $postData = PostService::postTypeBody($post);
                        PublishPinterestPost::dispatch($post->id, $postData, $access_token, 'link');
                    }
                    if ($account->type == 'tiktok' && ! empty($image)) {
                        $localImage = saveImageFromUrl($image, 'uploads');
                        if (empty($content) || empty($localImage)) {
                            return [
                                'success' => false,
                                'message' => 'Content or image is required.',
                            ];
                        }

                        // store in db as photo post (not link)
                        $data = [
                            'user_id' => $user->id,
                            'account_id' => $account->id,
                            'social_type' => 'tiktok',
                            'type' => 'photo', // Changed from "link" to "photo"
                            'source' => $this->source,
                            'title' => $url, // Use content from modal textarea (title)
                            'comment' => $comment,
                            'url' => $url,
                            'image' => $localImage, // Store thumbnail image URL
                            'status' => 0,
                            'publish_date' => $publishDateNow,
                        ];
                        $post = PostService::create($data);

                        // Verify account belongs to user before incrementing
                        if ($this->verifyPostAccountBelongsToUser($post, $user)) {
                            $user->incrementFeatureUsage('scheduled_posts_per_account', 1);
                        }

                        // Use validateToken for proper error handling
                        $tokenResponse = TikTokService::validateToken($account);
                        if (! $tokenResponse['success']) {
                            return [
                                'success' => false,
                                'message' => $tokenResponse['message'] ?? 'Failed to validate TikTok access token.',
                            ];
                        }
                        $access_token = $tokenResponse['access_token'];
                        $postData = PostService::postTypeBody($post);
                        PublishTikTokPost::dispatch($post->id, $postData, $access_token, 'photo'); // Changed from "link" to "photo"
                    }
                    if ($account->type === 'linkedin') {
                        $data = [
                            'user_id' => $user->id,
                            'account_id' => $account->id,
                            'social_type' => 'linkedin',
                            'type' => 'link',
                            'source' => $this->source,
                            'title' => $content,
                            'comment' => $comment,
                            'url' => $url,
                            'image' => $image,
                            'status' => 0,
                            'publish_date' => $publishDateNow,
                        ];
                        $post = PostService::create($data);
                        if ($this->verifyPostAccountBelongsToUser($post, $user)) {
                            $user->incrementFeatureUsage('scheduled_posts_per_account', 1);
                        }
                        PublishLinkedInPost::dispatch($post->id);
                    }
                }
                $response = [
                    'success' => true,
                    'message' => 'Your posts are being Published!',
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Invalid link provided!',
                ];
            }
        } catch (Exception $e) {
            $response = [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
        sleep(1);

        return $response;
    }

    // publish link post
    private function queueLink($request)
    {
        try {
            $user = User::with('boards.pinterest', 'pages.facebook')->find(Auth::guard('user')->id());
            $accounts = $this->resolveAccountsForPost($request, $user);
            $content = $request->get('content') ?? null;
            $comment = $request->get('comment') ?? null;
            $url = $request->get('url') ?? null;
            $image = $request->get('image') ?? null;
            if (! empty($url)) {
                $queueValidation = $this->validateQueueAccountsHaveTimeslots($accounts);
                if ($queueValidation !== null) {
                    return $queueValidation;
                }

                // Count how many posts will be created (link allowed without image for Facebook/Instagram)
                $postsToCreate = 0;
                foreach ($accounts as $account) {
                    if (count($account->timeslots) > 0 && ($account->type ?? '') !== 'instagram') {
                        $postsToCreate++;
                    }
                }

                // Check scheduled posts limit before creating any posts
                if ($postsToCreate > 0) {
                    /** @var User $user */
                    $limitCheck = $this->checkScheduledPostsLimit($user, $postsToCreate);
                    if (! $limitCheck['allowed']) {
                        return [
                            'success' => false,
                            'message' => $limitCheck['message'],
                        ];
                    }
                }

                foreach ($accounts as $account) {
                    if (count($account->timeslots) > 0) {
                        if ($account->type == 'facebook') {
                            Facebook::where('id', $account->fb_id)->firstOrFail();
                            $nextTime = (new Post)->nextScheduleTime(['account_id' => $account->id, 'social_type' => 'facebook', 'source' => 'schedule'], $account->timeslots, $user);
                            // store in db (image may be empty for Facebook/Instagram links)
                            $data = [
                                'user_id' => $user->id,
                                'account_id' => $account->id,
                                'social_type' => 'facebook',
                                'type' => 'link',
                                'source' => $this->source,
                                'title' => $content,
                                'comment' => $comment,
                                'url' => $url,
                                'image' => $image,
                                'status' => 0,
                                'publish_date' => $nextTime,
                            ];
                            $post = PostService::create($data);

                            // Verify account belongs to user before incrementing
                            if ($this->verifyPostAccountBelongsToUser($post, $user)) {
                                $user->incrementFeatureUsage('scheduled_posts_per_account', 1);
                            }
                        }
                        if ($account->type == 'pinterest') {
                            $pinterest = Pinterest::where('id', $account->pin_id)->firstOrFail();
                            if ($pinterest) {
                                $nextTime = (new Post)->nextScheduleTime(['account_id' => $account->id, 'social_type' => 'pinterest', 'source' => 'schedule'], $account->timeslots, $user);
                                // store in db
                                $data = [
                                    'user_id' => $user->id,
                                    'account_id' => $account->id,
                                    'social_type' => 'pinterest',
                                    'type' => 'link',
                                    'source' => $this->source,
                                    'title' => $content,
                                    'comment' => $comment,
                                    'url' => $url,
                                    'image' => $image,
                                    'status' => 0,
                                    'publish_date' => $nextTime,
                                ];
                                $post = PostService::create($data);

                                // Verify account belongs to user before incrementing
                                if ($this->verifyPostAccountBelongsToUser($post, $user)) {
                                    $user->incrementFeatureUsage('scheduled_posts_per_account', 1);
                                }
                            }
                        }
                        if ($account->type == 'tiktok' && ! empty($image)) {
                            $tiktok = Tiktok::where('id', $account->id)->firstOrFail();
                            if ($tiktok) {
                                $localImage = saveImageFromUrl($image, 'uploads');
                                if (empty($content) || empty($localImage)) {
                                    return [
                                        'success' => false,
                                        'message' => 'Content or image is required.',
                                    ];
                                }

                                $nextTime = (new Post)->nextScheduleTime(['account_id' => $account->id, 'social_type' => 'tiktok', 'source' => 'schedule'], $account->timeslots, $user);

                                // store in db as photo post (not link)
                                $data = [
                                    'user_id' => $user->id,
                                    'account_id' => $account->id,
                                    'social_type' => 'tiktok',
                                    'type' => 'photo', // Changed from "link" to "photo"
                                    'source' => $this->source,
                                    'title' => $url, // Use content from modal textarea (title)
                                    'comment' => $comment,
                                    'url' => $url,
                                    'image' => $localImage,
                                    'status' => 0,
                                    'publish_date' => $nextTime,
                                ];
                                $post = PostService::create($data);

                                // Verify account belongs to user before incrementing
                                if ($this->verifyPostAccountBelongsToUser($post, $user)) {
                                    $user->incrementFeatureUsage('scheduled_posts_per_account', 1);
                                }
                            }
                        }
                        if ($account->type === 'linkedin') {
                            $nextTime = (new Post)->nextScheduleTime(
                                ['account_id' => $account->id, 'social_type' => 'linkedin', 'source' => 'schedule'],
                                $account->timeslots,
                                $user
                            );
                            $data = [
                                'user_id' => $user->id,
                                'account_id' => $account->id,
                                'social_type' => 'linkedin',
                                'type' => 'link',
                                'source' => $this->source,
                                'title' => $content,
                                'comment' => $comment,
                                'url' => $url,
                                'image' => $image,
                                'status' => 0,
                                'publish_date' => $nextTime,
                            ];
                            $post = PostService::create($data);
                            if ($this->verifyPostAccountBelongsToUser($post, $user)) {
                                $user->incrementFeatureUsage('scheduled_posts_per_account', 1);
                            }
                        }
                    }
                }
                $response = [
                    'success' => true,
                    'message' => 'Your posts are queued for Later!',
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Invalid link provided!',
                ];
            }
        } catch (Exception $e) {
            $response = [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
        sleep(1);

        return $response;
    }

    // publish link post
    private function scheduleLink($request)
    {
        try {
            $user = User::with('boards.pinterest', 'pages.facebook')->find(Auth::guard('user')->id());
            $accounts = $this->resolveAccountsForPost($request, $user);
            $content = $request->get('content') ?? null;
            $comment = $request->get('comment') ?? null;
            $schedule_date = $request->schedule_date;
            $schedule_time = $request->schedule_time;
            $url = $request->get('url') ?? null;
            $image = $request->get('image') ?? null;
            if (! empty($url)) {
                $totalPostsToCreate = 0;
                foreach ($accounts as $acc) {
                    if (($acc->type ?? '') !== 'instagram') {
                        $totalPostsToCreate++;
                    }
                }

                // Check scheduled posts limit before creating any posts
                /** @var User $user */
                $limitCheck = $this->checkScheduledPostsLimit($user, $totalPostsToCreate);
                if (! $limitCheck['allowed']) {
                    return [
                        'success' => false,
                        'message' => $limitCheck['message'],
                    ];
                }

                foreach ($accounts as $account) {
                    $scheduleDateTime = date('Y-m-d', strtotime($schedule_date)) . ' ' . date('H:i', strtotime($schedule_time));
                    if ($account->type == 'facebook') {
                        Facebook::where('id', $account->fb_id)->firstOrFail();
                        // store in db (image may be empty for Facebook/Instagram links)
                        $data = [
                            'user_id' => $user->id,
                            'account_id' => $account->id,
                            'social_type' => 'facebook',
                            'type' => 'link',
                            'source' => $this->source,
                            'title' => $content,
                            'comment' => $comment,
                            'url' => $url,
                            'image' => $image,
                            'status' => 0,
                            'publish_date' => $scheduleDateTime,
                        ];
                        $post = PostService::create($data);

                        // Verify account belongs to user before incrementing
                        if ($this->verifyPostAccountBelongsToUser($post, $user)) {
                            $user->incrementFeatureUsage('scheduled_posts_per_account', 1);
                        }
                    }
                    if ($account->type == 'pinterest') {
                        $pinterest = Pinterest::where('id', $account->pin_id)->firstOrFail();
                        if ($pinterest) {
                            // store in db
                            $data = [
                                'user_id' => $user->id,
                                'account_id' => $account->id,
                                'social_type' => 'pinterest',
                                'type' => 'link',
                                'source' => $this->source,
                                'title' => $content,
                                'comment' => $comment,
                                'url' => $url,
                                'image' => $image,
                                'status' => 0,
                                'publish_date' => $scheduleDateTime,
                            ];
                            $post = PostService::create($data);

                            // Verify account belongs to user before incrementing
                            if ($this->verifyPostAccountBelongsToUser($post, $user)) {
                                $user->incrementFeatureUsage('scheduled_posts_per_account', 1);
                            }
                        }
                    }
                    if ($account->type == 'tiktok' && ! empty($image)) {
                        $tiktok = Tiktok::where('id', $account->id)->firstOrFail();
                        if ($tiktok) {
                            $localImage = saveImageFromUrl($image, 'uploads');
                            if (empty($content) || empty($localImage)) {
                                return [
                                    'success' => false,
                                    'message' => 'Content or image is required.',
                                ];
                            }

                            // store in db as photo post (not link)
                            $data = [
                                'user_id' => $user->id,
                                'account_id' => $account->id,
                                'social_type' => 'tiktok',
                                'type' => 'photo', // Changed from "link" to "photo"
                                'source' => $this->source,
                                'title' => $url, // Use content from modal textarea (title)
                                'comment' => $comment,
                                'url' => $url,
                                'image' => $localImage,
                                'status' => 0,
                                'publish_date' => $scheduleDateTime,
                            ];
                            $post = PostService::create($data);

                            // Verify account belongs to user before incrementing
                            if ($this->verifyPostAccountBelongsToUser($post, $user)) {
                                $user->incrementFeatureUsage('scheduled_posts_per_account', 1);
                            }
                        }
                    }
                    if ($account->type === 'linkedin') {
                        $data = [
                            'user_id' => $user->id,
                            'account_id' => $account->id,
                            'social_type' => 'linkedin',
                            'type' => 'link',
                            'source' => $this->source,
                            'title' => $content,
                            'comment' => $comment,
                            'url' => $url,
                            'image' => $image,
                            'status' => 0,
                            'publish_date' => $scheduleDateTime,
                        ];
                        $post = PostService::create($data);
                        if ($this->verifyPostAccountBelongsToUser($post, $user)) {
                            $user->incrementFeatureUsage('scheduled_posts_per_account', 1);
                        }
                    }
                }
                $response = [
                    'success' => true,
                    'message' => 'Your posts are scheduled for Later!',
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Invalid link provided!',
                ];
            }
        } catch (Exception $e) {
            $response = [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
        sleep(1);

        return $response;
    }

    public function getSetting(Request $request)
    {
        $user = User::with('boards.pinterest', 'pages.facebook')->find(Auth::guard('user')->id());
        $accounts = $user->getAccounts();
        $view = view('user.schedule.ajax.settings', compact('accounts'));
        $response = [
            'success' => true,
            'data' => $view->render(),
        ];

        return response()->json($response);
    }

    public function getQueueSettings(Request $request)
    {
        $user = User::with('boards.pinterest', 'pages.facebook', 'tiktok', 'instagramAccounts', 'threads.timeslots', 'linkedins.timeslots')->find(Auth::guard('user')->id());
        $accounts = $user->getAccounts();
        $view = view('user.schedule.partials.queue-settings-list', compact('accounts'));

        return response()->json([
            'success' => true,
            'data' => $view->render(),
        ]);
    }

    public function timeslotSetting(Request $request)
    {
        $user = Auth::guard('user')->user();
        try {
            $type = $request->type;
            $id = $request->id;
            $timeslots = $request->timeslots;
            $account = null;
            if ($type == 'facebook') {
                $account = Page::with('timeslots')->where('id', $id)->firstOrFail();
                $account_id = $account->id;
            } elseif ($type == 'pinterest') {
                $account = Board::with('timeslots')->where('id', $id)->firstOrFail();
                $account_id = $account->id;
            } elseif ($type == 'tiktok') {
                $account = Tiktok::with('timeslots')->where('id', $id)->firstOrFail();
                $account_id = $account->id;
            } elseif ($type == 'instagram') {
                $account = InstagramAccount::with('scheduleTimeslots')->where('id', $id)->where('user_id', $user->id)->firstOrFail();
                $account_id = $account->id;
            } elseif ($type == 'threads') {
                $account = Thread::with('timeslots')->where('id', $id)->where('user_id', $user->id)->firstOrFail();
                $account_id = $account->id;
            } elseif ($type == 'linkedin') {
                $account = Linkedin::with('timeslots')->where('id', $id)->where('user_id', $user->id)->firstOrFail();
                $account_id = $account->id;
            } elseif ($type == 'youtube') {
                $account = Youtube::with('timeslots')->where('id', $id)->where('user_id', $user->id)->firstOrFail();
                $account_id = $account->id;
            }
            if ($account) {
                // remove previous
                Timeslot::where('account_id', $account_id)->where('account_type', $type)->where('type', 'schedule')->delete();
                // create new timeslots
                if (is_array($timeslots)) {
                    foreach ($timeslots as $timeslot) {
                        Timeslot::create([
                            'user_id' => $user->id,
                            'account_id' => $account_id,
                            'account_type' => $type,
                            'timeslot' => date('H:i', strtotime($timeslot)),
                            'type' => 'schedule',
                        ]);
                    }
                }
                $response = [
                    'success' => true,
                    'message' => 'Timeslot updated Successfully!',
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Something went Wrong!',
                ];
            }
        } catch (Exception $e) {
            $response = [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }

        return response()->json($response);
    }

    /**
     * Update shuffle status via route. Accepts account_id and schedule_shuffle (0 or 1).
     * Only handles shuffle - nothing else.
     */
    public function updateShuffleStatus(Request $request)
    {
        $request->validate([
            'account_id' => 'required|integer',
            'schedule_shuffle' => 'required|integer|in:0,1',
        ]);

        $user = Auth::guard('user')->user();
        $page = Page::where('id', $request->account_id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $this->updateScheduleShuffleStatus($page, (int) $request->schedule_shuffle, $user);

        return response()->json([
            'success' => true,
            'message' => 'Shuffle status updated successfully.',
        ]);
    }

    /**
     * Update schedule shuffle status and shuffle posts when enabled.
     * Only handles shuffle - nothing else.
     */
    private function updateScheduleShuffleStatus(Page $page, int $scheduleShuffle, User $user): void
    {
        $page->update(['schedule_shuffle' => $scheduleShuffle]);

        if ($scheduleShuffle !== 1) {
            return;
        }

        $postsToShuffle = Post::where('user_id', $user->id)
            ->where('account_id', $page->id)
            ->where('social_type', 'like', '%facebook%')
            ->where('status', 0)
            ->where('source', 'schedule')
            ->get();

        if ($postsToShuffle->count() >= 2) {
            $publishDates = $postsToShuffle->pluck('publish_date')->shuffle()->values();
            DB::transaction(function () use ($postsToShuffle, $publishDates) {
                foreach ($postsToShuffle as $index => $post) {
                    $post->update(['publish_date' => $publishDates[$index] ?? $post->publish_date]);
                }
            });
        }
    }

    public function saveTimeslotSettings(Request $request)
    {
        $user = Auth::guard('user')->user();
        $timeslotData = $request->timeslot_data ?? [];

        if (empty($timeslotData) || ! is_array($timeslotData)) {
            return response()->json([
                'success' => false,
                'message' => 'No timeslot data provided!',
            ]);
        }

        try {
            foreach ($timeslotData as $item) {
                $type = $item['type'] ?? null;
                $id = $item['id'] ?? null;
                $timeslots = $item['timeslots'] ?? [];

                if (! $type || ! $id || empty($timeslots)) {
                    continue;
                }

                $account = null;
                $accountId = null;

                if ($type == 'facebook') {
                    $account = Page::with('timeslots')->where('id', $id)->first();
                    if ($account) {
                        $accountId = $account->id;
                    }
                } elseif ($type == 'pinterest') {
                    $account = Board::with('timeslots')->where('id', $id)->first();
                    if ($account) {
                        $accountId = $account->id;
                    }
                } elseif ($type == 'tiktok') {
                    $account = Tiktok::with('timeslots')->where('id', $id)->first();
                    if ($account) {
                        $accountId = $account->id;
                    }
                } elseif ($type == 'instagram') {
                    $account = InstagramAccount::with('scheduleTimeslots')->where('id', $id)->where('user_id', $user->id)->first();
                    if ($account) {
                        $accountId = $account->id;
                    }
                } elseif ($type == 'threads') {
                    $account = Thread::with('timeslots')->where('id', $id)->where('user_id', $user->id)->first();
                    if ($account) {
                        $accountId = $account->id;
                    }
                } elseif ($type == 'linkedin') {
                    $account = Linkedin::with('timeslots')->where('id', $id)->where('user_id', $user->id)->first();
                    if ($account) {
                        $accountId = $account->id;
                    }
                } elseif ($type == 'youtube') {
                    $account = Youtube::with('timeslots')->where('id', $id)->where('user_id', $user->id)->first();
                    if ($account) {
                        $accountId = $account->id;
                    }
                }

                if ($account && $accountId) {
                    // Remove previous timeslots
                    Timeslot::where('account_id', $accountId)
                        ->where('account_type', $type)
                        ->where('type', 'schedule')
                        ->delete();

                    // Create new timeslots
                    foreach ($timeslots as $timeslot) {
                        Timeslot::create([
                            'user_id' => $user->id,
                            'account_id' => $accountId,
                            'account_type' => $type,
                            'timeslot' => date('H:i', strtotime($timeslot)),
                            'type' => 'schedule',
                        ]);
                    }

                    // Rearrange posts according to updated timeslots
                    $allTimeslots = [];
                    foreach ($timeslots as $timeslot) {
                        $allTimeslots[] = $timeslot;
                    }

                    if (! empty($allTimeslots)) {
                        // Sort timeslots chronologically
                        usort($allTimeslots, function ($a, $b) {
                            $timeA = strtotime($a);
                            $timeB = strtotime($b);

                            return $timeA - $timeB;
                        });

                        // Get all unpublished scheduled posts for this account
                        $postsQuery = Post::with('user.timezone')
                            ->where('account_id', $accountId)
                            ->where('status', '!=', 1) // Not published
                            ->where('scheduled', 1); // Scheduled posts only
                        $posts = $postsQuery->orderBy('publish_date', 'ASC')->get();

                        if ($posts->count() > 0) {
                            $currentDateTime = now();
                            $currentDate = $currentDateTime->format('Y-m-d');
                            $currentTime = $currentDateTime->format('H:i:s');

                            $currentScheduleDate = $currentDate;
                            $timeslotIndex = 0;
                            // Track used timeslots for each date
                            $usedTimeslotsByDate = [];

                            foreach ($posts as $post) {
                                $assigned = false;

                                // Initialize used timeslots array for current schedule date if not exists
                                if (! isset($usedTimeslotsByDate[$currentScheduleDate])) {
                                    $usedTimeslotsByDate[$currentScheduleDate] = [];
                                }

                                // First, check if there are any available timeslots for the current date
                                $availableTimeslotForCurrentDate = null;
                                $availableTimeslotIndex = null;

                                if ($currentScheduleDate == $currentDate) {
                                    // Check current date for available timeslots
                                    foreach ($allTimeslots as $idx => $timeslot) {
                                        $timeslot24Hour = date('H:i:s', strtotime($timeslot));
                                        $timeslotKey = $timeslot24Hour;

                                        // Check if timeslot is available (not passed and not used)
                                        if (
                                            $timeslot24Hour > $currentTime &&
                                            ! in_array($timeslotKey, $usedTimeslotsByDate[$currentScheduleDate])
                                        ) {
                                            $availableTimeslotForCurrentDate = $timeslot;
                                            $availableTimeslotIndex = $idx;
                                            break;
                                        }
                                    }
                                }

                                // If available timeslot found for current date, use it
                                if ($availableTimeslotForCurrentDate !== null) {
                                    $timeslot24Hour = date('H:i:s', strtotime($availableTimeslotForCurrentDate));
                                    $timeslotKey = $timeslot24Hour;

                                    // Assign post to this timeslot on current date (convert to UTC for storage)
                                    $publishDateTimeLocal = $currentScheduleDate . ' ' . $timeslot24Hour;
                                    $post->update([
                                        'publish_date' => TimezoneService::toUtc($publishDateTimeLocal, $user),
                                    ]);

                                    // Mark timeslot as used for this date
                                    $usedTimeslotsByDate[$currentScheduleDate][] = $timeslotKey;

                                    // Move to next timeslot index
                                    $timeslotIndex = ($availableTimeslotIndex + 1) % count($allTimeslots);

                                    // If we've used all timeslots for this day, move to next day
                                    if (count($usedTimeslotsByDate[$currentScheduleDate]) >= count($allTimeslots)) {
                                        $currentScheduleDate = date('Y-m-d', strtotime($currentScheduleDate . ' +1 day'));
                                        $timeslotIndex = 0;
                                        if (! isset($usedTimeslotsByDate[$currentScheduleDate])) {
                                            $usedTimeslotsByDate[$currentScheduleDate] = [];
                                        }
                                    }

                                    $assigned = true;
                                } else {
                                    // No available timeslot for current date, find next available
                                    $attempts = 0;
                                    $maxAttempts = count($allTimeslots) * 100; // Safety limit

                                    while (! $assigned && $attempts < $maxAttempts) {
                                        // Get current timeslot
                                        $timeslot = $allTimeslots[$timeslotIndex];

                                        // Convert timeslot to 24-hour format
                                        $timeslot24Hour = date('H:i:s', strtotime($timeslot));

                                        // Initialize used timeslots array for current schedule date if not exists
                                        if (! isset($usedTimeslotsByDate[$currentScheduleDate])) {
                                            $usedTimeslotsByDate[$currentScheduleDate] = [];
                                        }

                                        // Check if timeslot is already used for this date
                                        $timeslotKey = $timeslot24Hour;
                                        if (in_array($timeslotKey, $usedTimeslotsByDate[$currentScheduleDate])) {
                                            // Timeslot already used for this date, try next timeslot
                                            $timeslotIndex++;
                                            if ($timeslotIndex >= count($allTimeslots)) {
                                                // All timeslots used for this day, move to next day and reset timeslot index
                                                $currentScheduleDate = date('Y-m-d', strtotime($currentScheduleDate . ' +1 day'));
                                                $timeslotIndex = 0;
                                                // Reset used timeslots tracking for new date (timeslots reset on new date)
                                                if (! isset($usedTimeslotsByDate[$currentScheduleDate])) {
                                                    $usedTimeslotsByDate[$currentScheduleDate] = [];
                                                }
                                            }
                                            $attempts++;

                                            continue;
                                        }

                                        // Check if timeslot has passed for current day
                                        if ($currentScheduleDate == $currentDate && $timeslot24Hour <= $currentTime) {
                                            // Timeslot has passed, move to next day (keep same timeslot index since it's a new date)
                                            $currentScheduleDate = date('Y-m-d', strtotime($currentScheduleDate . ' +1 day'));
                                            // Reset used timeslots tracking for new date (timeslots reset on new date)
                                            if (! isset($usedTimeslotsByDate[$currentScheduleDate])) {
                                                $usedTimeslotsByDate[$currentScheduleDate] = [];
                                            }
                                            $attempts++;

                                            continue;
                                        }

                                        // Timeslot is available for this date, assign post (convert to UTC for storage)
                                        $publishDateTimeLocal = $currentScheduleDate . ' ' . $timeslot24Hour;
                                        $post->update([
                                            'publish_date' => TimezoneService::toUtc($publishDateTimeLocal, $user),
                                        ]);

                                        // Mark timeslot as used for this date
                                        $usedTimeslotsByDate[$currentScheduleDate][] = $timeslotKey;

                                        // Move to next timeslot
                                        $timeslotIndex++;
                                        if ($timeslotIndex >= count($allTimeslots)) {
                                            // All timeslots used for this day, move to next day and reset timeslot index
                                            $currentScheduleDate = date('Y-m-d', strtotime($currentScheduleDate . ' +1 day'));
                                            $timeslotIndex = 0;
                                            // Reset used timeslots tracking for new date
                                            if (! isset($usedTimeslotsByDate[$currentScheduleDate])) {
                                                $usedTimeslotsByDate[$currentScheduleDate] = [];
                                            }
                                        }

                                        $assigned = true;
                                    }
                                }
                            }
                        }
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Timeslot settings saved and posts rearranged successfully!',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Listing filter is exactly one platform: Pinterest (used to allow full sent list without pagination).
     */
    private function requestIsPinterestOnlyListingTypes(Request $request): bool
    {
        $types = array_values(array_filter(
            (array) $request->input('type', []),
            fn($t) => $t !== null && $t !== ''
        ));

        return count($types) === 1 && (string) $types[0] === 'pinterest';
    }

    /**
     * Sent-tab window for rows sourced from posts table.
     */
    private function sentPostsRecentCutoffUtc(): Carbon
    {
        return Carbon::now('UTC')->subHours(3);
    }

    public function postsListing(Request $request)
    {
        $data = $request->all();
        $viewer = Auth::guard('user')->user();
        $postCreatorIds = $viewer instanceof User ? $viewer->schedulePostCreatorUserIds() : [(int) Auth::guard('user')->id()];

        $posts = Post::withoutGlobalScopes()
            ->with('page.facebook', 'board.pinterest', 'instagramAccount', 'user.timezone')
            ->isScheduled()
            ->whereIn('user_id', $postCreatorIds);

        if (! empty($request->account_id)) {
            $posts = $posts->whereIn('account_id', $request->account_id);
        }
        if (! empty($request->type)) {
            $posts = $posts->whereIn('social_type', $request->type);
        }
        if (! empty($request->post_type)) {
            $posts = $posts->whereIn('type', $request->post_type);
        }

        $tab = $request->input('post_status_tab');
        if ($tab === 'sent') {
            $posts = $posts->where('status', 1);
            $posts = $posts->where('published_at', '>=', $this->sentPostsRecentCutoffUtc());
        } elseif ($tab === 'failed') {
            $posts = $posts->where('status', -1);
        } elseif ($tab === 'queue') {
            $posts = $posts->where('status', 0);
        } elseif ($request->has('status')) {
            $posts = $posts->where('status', $request->status);
        }

        $totalRecordswithFilter = clone $posts;

        $sortDir = ($tab === 'sent' || $tab === 'failed') ? 'desc' : 'asc';
        $sortCol = ($tab === 'sent') ? 'published_at' : 'publish_date';

        $loadAllPinterestSent = $request->boolean('sent_load_all')
            && $tab === 'sent'
            && $this->requestIsPinterestOnlyListingTypes($request);

        if ($loadAllPinterestSent) {
            $posts = (clone $totalRecordswithFilter)->orderBy($sortCol, $sortDir)->get();
        } else {
            $posts = (clone $totalRecordswithFilter)
                ->offset(intval($data['start'] ?? 0))
                ->limit(intval($data['length'] ?? 9))
                ->orderBy($sortCol, $sortDir)
                ->get();
        }

        $posts->append(['post_details', 'account_detail', 'publish_datetime', 'status_view', 'action', 'account_name', 'account_profile', 'published_at_formatted', 'facebook_post_url']);
        $response = [
            'draw' => intval($data['draw']),
            'iTotalRecords' => Post::count(),
            'iTotalDisplayRecords' => $totalRecordswithFilter->count(),
            'data' => $posts,
        ];

        return response()->json($response);
    }

    /**
     * Summary of postDelete
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function postDelete(Request $request)
    {
        try {
            /** @var User|null $viewer */
            $viewer = Auth::guard('user')->user();
            if (! $viewer instanceof User) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
            }

            $postId = (int) $request->input('id', 0);
            if ($postId <= 0) {
                return response()->json(['success' => false, 'message' => 'Invalid post.'], 422);
            }

            $postCreatorIds = $viewer->schedulePostCreatorUserIds();
            $post = Post::withoutGlobalScopes()
                ->whereIn('user_id', $postCreatorIds)
                ->where('id', $postId)
                ->first();

            if ($post) {
                $owner = User::findOrFail($post->user_id);
                // Decrement feature usage if this is a scheduled post and account belongs to user
                if ($post->source === 'schedule' && $this->verifyPostAccountBelongsToUser($post, $owner)) {
                    $owner->decrementFeatureUsage('scheduled_posts_per_account', 1);
                }

                $post->photo()->delete();
                PostService::delete($post->id);
            } else {
                // Post not in database - delete from Facebook page only
                $facebookPostId = $request->facebook_post_id;
                $pageId = $request->page_id;
                if ($facebookPostId && $pageId) {
                    $page = Page::where('id', $pageId)->where('user_id', Auth::guard('user')->id())->first();
                    if ($page) {
                        DeleteFacebookPostJob::dispatch($facebookPostId, (int) $pageId, null);
                    }
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Post not found or you do not have access to delete it.',
                    ], 404);
                }
            }

            $response = [
                'success' => true,
                'message' => 'Post deleted successfully!',
            ];
        } catch (Exception $e) {
            $response = [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }

        return response()->json($response);
    }

    public function postEdit(Request $request)
    {
        try {
            $post = Post::with('page.facebook', 'board.pinterest')->findOrFail($request->id);
            $view = view('user.schedule.ajax.edit-post', compact('post'));
            $response = [
                'success' => true,
                'data' => $view->render(),
                'action' => route('panel.schedule.post.update', $post->id),
            ];
        } catch (Exception $e) {
            $response = [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }

        return response()->json($response);
    }

    public function postUpdate($id, Request $request)
    {
        try {
            $post = Post::with('user')->findOrFail($id);
            $publishDateTimeLocal = date('Y-m-d', strtotime($request->edit_post_publish_date)) . ' ' . date('H:i', strtotime($request->edit_post_publish_time));
            $data = [
                'title' => $request->edit_post_title,
                'url' => $request->edit_post_link,
                'comment' => $request->edit_post_comment,
                'publish_date' => TimezoneService::toUtc($publishDateTimeLocal, $post->user),
            ];
            if ($request->has('edit_post_publish_image') && $request->File('edit_post_publish_image')) {
                $image = saveImage($request->file('edit_post_publish_image'));
                $data['image'] = $image;
            }
            $post->update($data);
            $response = [
                'success' => true,
                'message' => 'Post updated Successfully!',
            ];
        } catch (Exception $e) {
            $response = [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }

        return response()->json($response);
    }

    public function postUpdateComment($id, Request $request)
    {
        try {
            $post = Post::findOrFail($id);
            $post->update(['comment' => $request->input('comment', '')]);

            return response()->json([
                'success' => true,
                'message' => 'Comment updated successfully.',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function postPublishNow(Request $request)
    {
        $response = PostService::publishNow($request->id);

        return response()->json($response);
    }

    public function postsStatusCounts(Request $request)
    {
        $accountIds = $request->input('account_id', []);
        $accountTypes = $request->input('type', []);
        $soruce = $request->input('source', 'schedule');

        if (empty($accountIds)) {
            return response()->json(['queue' => 0, 'sent' => 0, 'failed' => 0]);
        }

        $base = Post::withoutGlobalScopes()
            ->whereIn('account_id', (array) $accountIds);
        if ($soruce) {
            $base->where('source', $soruce);
        }

        $user = Auth::guard('user')->user();
        $postCreatorIds = $user instanceof User ? $user->schedulePostCreatorUserIds() : [(int) Auth::guard('user')->id()];
        $base->whereIn('user_id', $postCreatorIds);

        $userTz = TimezoneService::getUserTimezone($user);
        $nowUtc = Carbon::now($userTz)->setTimezone('UTC')->format('Y-m-d H:i:s');

        $queue = (clone $base)->where('status', 0)->where('publish_date', '>=', $nowUtc)->count();
        $sent = (clone $base)->where('status', 1)->count();

        return response()->json(['queue' => $queue, 'sent' => $sent]);
    }

    public function getTimeslots(Request $request)
    {
        $accountId = $request->input('account_id');
        $accountType = $request->input('type');

        if (! $accountId || ! $accountType) {
            return response()->json(['timeslots' => []]);
        }

        /** @var User|null $user */
        $user = Auth::guard('user')->user();
        if (! $user instanceof User) {
            return response()->json(['timeslots' => []]);
        }

        $resolvedId = (int) $accountId;
        $resolvedType = (string) $accountType;

        if (strtolower($resolvedType) === 'instagram') {
            $ig = InstagramAccount::where('id', $resolvedId)->where('user_id', $user->id)->first();
            if (! $ig) {
                return response()->json(['timeslots' => []]);
            }
            $timeslots = $ig->timeslots->pluck('timeslot')->sort()->values()->map(function ($slot) {
                return date('h:i A', strtotime($slot));
            });

            return response()->json(['timeslots' => $timeslots]);
        }

        $timeslots = Timeslot::where('account_id', $resolvedId)
            ->where('account_type', $resolvedType)
            ->pluck('timeslot')
            ->sort()
            ->values()
            ->map(function ($slot) {
                return date('h:i A', strtotime($slot));
            });

        return response()->json(['timeslots' => $timeslots]);
    }

    public function getQueueTimeline(Request $request)
    {
        $accountId = $request->input('account_id');
        $accountType = $request->input('account_type') ?? $request->input('type');
        $days = (int) $request->input('days', 14);
        $offset = (int) $request->input('offset', 0);
        $source = $request->input('source', 'schedule');

        if (! $accountId || ! $accountType) {
            return response()->json(['success' => false, 'message' => 'Account required']);
        }

        $user = Auth::guard('user')->user();
        if (! $user instanceof User) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        $userTz = TimezoneService::getUserTimezone($user);

        $timeslots = $this->queueScheduleTimeslotStringsForAccount(
            $user,
            (int) $accountId,
            (string) $accountType
        );

        $socialType = match (strtolower((string) $accountType)) {
            'pinterest' => 'pinterest',
            'tiktok' => 'tiktok',
            'instagram' => 'instagram',
            'threads' => 'threads',
            'linkedin' => 'linkedin',
            'youtube' => 'youtube',
            default => 'facebook',
        };

        $startDate = Carbon::now($userTz)->addDays($offset)->startOfDay();
        $endDate = $startDate->copy()->addDays($days - 1)->endOfDay();

        $postCreatorIds = $user->schedulePostCreatorUserIds();

        $postsQuery = Post::withoutGlobalScopes()
            ->with('page.facebook', 'board.pinterest', 'tiktok', 'instagramAccount', 'thread', 'linkedin', 'youtube', 'user')
            ->whereIn('user_id', $postCreatorIds)
            ->where('account_id', $accountId)
            ->where('social_type', 'like', "%{$socialType}%")
            ->where('status', 0)
            ->whereBetween('publish_date', [
                $startDate->copy()->setTimezone('UTC')->format('Y-m-d H:i:s'),
                $endDate->copy()->setTimezone('UTC')->format('Y-m-d H:i:s'),
            ]);
        if ($source) {
            $postsQuery->where('source', $source);
        }
        $existingPostsByKey = $postsQuery->get()->groupBy(function ($post) use ($userTz) {
            return Carbon::parse($post->publish_date, 'UTC')->setTimezone($userTz)->format('Y-m-d H:i');
        });

        $timeline = [];
        $now = Carbon::now($userTz);

        $formatPostForSlot = function ($post) use ($user) {
            $author = $post->user;
            $createdByName = $author
                ? trim((string) ($author->full_name ?: (($author->username ?? '') !== '' ? $author->username : ($author->email ?? ''))))
                : '';

            return [
                'id' => $post->id,
                'title' => $post->title,
                'comment' => $post->comment,
                'description' => $post->description,
                'url' => $post->url,
                'type' => $post->type,
                'status' => (int) $post->status,
                'image' => $this->queueTimelinePostImageUrl($post),
                'video' => $this->postStoredVideoUrl($post),
                'carousel_items' => $this->instagramCarouselGalleryItemsFromPost($post),
                'account_name' => $post->account_name ?? ucfirst($post->social_type),
                'account_profile' => $post->account_profile ? (str_starts_with($post->account_profile, 'http') ? $post->account_profile : asset($post->account_profile)) : null,
                'social_type' => $post->social_type,
                'created_at' => $post->created_at ? Carbon::parse($post->created_at)->diffForHumans() : null,
                'is_own_post' => (int) $post->user_id === (int) $user->id,
                'created_by_name' => $createdByName,
            ];
        };

        for ($d = 0; $d < $days; $d++) {
            $date = $startDate->copy()->addDays($d);
            $dateStr = $date->format('Y-m-d');
            $daySlots = [];
            $placedKeys = [];

            foreach ($timeslots ?: [] as $slot) {
                $slotNormalized = date('H:i', strtotime($slot));
                $slotTime = Carbon::parse($dateStr . ' ' . $slotNormalized, $userTz);

                if ($date->isToday() && $slotTime->lt($now)) {
                    continue;
                }

                $key = $slotTime->format('Y-m-d H:i');
                $posts = $existingPostsByKey->get($key, collect());
                if ($posts->isNotEmpty()) {
                    $placedKeys[$key] = true;
                }

                $daySlots[] = [
                    'time' => $slotTime->format('H:i'),
                    'time_display' => $slotTime->format('h:i A'),
                    'datetime_utc' => $slotTime->copy()->setTimezone('UTC')->format('Y-m-d H:i:s'),
                    'has_post' => $posts->isNotEmpty(),
                    'posts' => $posts->map($formatPostForSlot)->values()->toArray(),
                ];
            }

            // Generate virtual slots for posts that don't match any configured timeslot
            foreach ($existingPostsByKey as $key => $posts) {
                if (str_starts_with($key, $dateStr . ' ') && ! isset($placedKeys[$key])) {
                    $slotTime = Carbon::parse($key, $userTz);
                    if ($date->isToday() && $slotTime->lt($now)) {
                        continue;
                    }
                    $daySlots[] = [
                        'time' => $slotTime->format('H:i'),
                        'time_display' => $slotTime->format('h:i A'),
                        'datetime_utc' => $slotTime->copy()->setTimezone('UTC')->format('Y-m-d H:i:s'),
                        'has_post' => true,
                        'posts' => $posts->map($formatPostForSlot)->values()->toArray(),
                    ];
                }
            }

            usort($daySlots, fn($a, $b) => strcmp($a['time'], $b['time']));

            if (! empty($daySlots)) {
                $isToday = $date->isToday();
                $isTomorrow = $date->isTomorrow();
                $label = $isToday ? 'Today' : ($isTomorrow ? 'Tomorrow' : $date->format('l'));

                $timeline[] = [
                    'date' => $date->format('Y-m-d'),
                    'label' => $label,
                    'date_display' => $date->format('F j'),
                    'slots' => $daySlots,
                ];
            }
        }

        return response()->json([
            'success' => true,
            'timeline' => $timeline,
            'has_more' => true,
            'next_offset' => $offset + $days,
        ]);
    }

    /**
     * Next slot that would be used when queueing a post (matches Post::nextScheduleTime used on queue).
     */
    public function getNextQueueSlot(Request $request)
    {
        $accountId = $request->input('account_id');
        $accountType = $request->input('type');

        if (! $accountId || ! $accountType) {
            return response()->json(['success' => false, 'message' => 'Account required']);
        }

        /** @var User|null $user */
        $user = Auth::guard('user')->user();
        if (! $user instanceof User) {
            return response()->json(['success' => false, 'message' => 'Unauthorized']);
        }

        $accounts = $user->getAccountsForPostCreation([
            ['id' => (int) $accountId, 'type' => (string) $accountType],
        ]);

        if ($accounts->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Account not found']);
        }

        $account = $accounts->first();
        $timeslots = $account->timeslots ?? collect();

        if ($timeslots->isEmpty()) {
            return response()->json([
                'success' => true,
                'has_timeslots' => false,
                'display' => '',
                'message' => 'Add posting hours in queue settings to use the queue.',
            ]);
        }

        $socialType = match ($account->type) {
            'pinterest' => 'pinterest',
            'tiktok' => 'tiktok',
            'instagram' => 'instagram',
            'threads' => 'threads',
            'linkedin' => 'linkedin',
            'youtube' => 'youtube',
            default => 'facebook',
        };
        $nextLocal = (new Post)->nextScheduleTime(
            ['account_id' => $account->id, 'social_type' => $socialType, 'source' => 'schedule'],
            $timeslots,
            $user
        );

        $userTz = TimezoneService::getUserTimezone($user);
        try {
            $dt = Carbon::parse($nextLocal, $userTz);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => true,
                'has_timeslots' => true,
                'display' => $nextLocal,
                'date_display' => '',
                'time_display' => '',
            ]);
        }

        return response()->json([
            'success' => true,
            'has_timeslots' => true,
            'date_display' => $dt->format('l, F j, Y'),
            'time_display' => $dt->format('h:i A'),
            'display' => $dt->format('D, M j') . ' · ' . $dt->format('h:i A'),
        ]);
    }

    public function getPageSentPosts(Request $request)
    {
        $userId = (int) Auth::id();
        $viewer = Auth::guard('user')->user();
        $postCreatorIds = $viewer instanceof User ? $viewer->schedulePostCreatorUserIds() : [$userId];
        $offset = max(0, (int) $request->input('offset', 0));
        $limitInput = $request->input('limit');
        $limit = is_null($limitInput) ? null : max(1, min(50, (int) $limitInput));
        $accountIds = (array) $request->input('account_id', []);
        if (empty($accountIds)) {
            return response()->json(['success' => false, 'message' => 'No account selected', 'posts' => []]);
        }

        $page = Page::whereIn('id', $accountIds)->first();
        if (empty($page)) {
            return response()->json(['success' => false, 'message' => 'No page found', 'posts' => []]);
        }

        // Sent tab (beta): full_year only — since = 1 year ago, until = today
        $until = now()->format('Y-m-d');
        $since = now()->subYear()->format('Y-m-d');
        $duration = 'full_year';

        $allPosts = [];
        $graphPostIds = [];

        $cacheKey = $this->facebookDurationPostsCacheKey((string) $page->page_id, $duration, $since, $until);
        $posts = null;
        $source = null;
        try {
            $posts = Cache::get($cacheKey);
            $source = 'cache';
        } catch (\Throwable $e) {
            Log::warning('Facebook sent posts cache read failed', [
                'key' => $cacheKey,
                'message' => $e->getMessage(),
            ]);
        }
        if ($posts === null) {
            $posts = $this->fetchPagePostsFromStore($page, $since, $until);
            if ($posts !== null) {
                try {
                    Cache::put($cacheKey, $posts, now()->addHours(self::POSTS_CACHE_TTL_HOURS));
                    $source = 'database';
                } catch (\Throwable $e) {
                    Log::warning('Facebook sent posts cache write failed', [
                        'key' => $cacheKey,
                        'message' => $e->getMessage(),
                    ]);
                }
            }
        }
        if (! is_array($posts)) {
            $posts = [];
        }

        // Avoid N+1 queries: preload our matching posts for this page response.
        $postIds = collect($posts)
            ->pluck('id')
            ->filter()
            ->map(fn($id) => (string) $id)
            ->unique()
            ->values()
            ->all();

        $ourPostsByExternalId = empty($postIds)
            ? collect()
            : Post::withoutGlobalScopes()
            ->whereIn('user_id', $postCreatorIds)
            ->whereIn('post_id', $postIds)
            ->with('user')
            ->get()
            ->keyBy(fn($p) => (string) $p->post_id);

        foreach ($posts as &$post) {
            $post['account_name'] = $page->name;
            $post['account_profile'] = $page->profile_image;
            $post['social_type'] = 'facebook';
            $post['page_db_id'] = $page->id;
            $ourPost = $ourPostsByExternalId->get((string) ($post['id'] ?? ''));
            if ($ourPost) {
                $post['db_post_id'] = $ourPost->id;
                if ($ourPost->user) {
                    $post['publisher_username'] = $ourPost->user->username ?? $ourPost->user->full_name ?? $ourPost->user->email ?? '';
                    $post['publisher_email'] = $ourPost->user->email ?? '';
                }
            }
        }
        unset($post);

        foreach ($posts as $p) {
            if (! empty($p['id'])) {
                $graphPostIds[(string) $p['id']] = true;
            }
        }

        $allPosts = array_merge($allPosts, $posts);

        // Posts published via the app but not yet present in facebook_posts / Graph cache: show from DB with zeroed metrics.
        $sinceStart = Carbon::parse($since)->startOfDay();
        $untilEnd = Carbon::parse($until)->endOfDay();
        $pageIds = [$page->id];
        $pagesById = collect([$page->id => $page]);

        $dbSentPosts = Post::withoutGlobalScopes()
            ->whereIn('user_id', $postCreatorIds)
            ->where('social_type', 'facebook')
            ->whereIn('account_id', $pageIds)
            ->where('status', 1)
            ->whereNotNull('post_id')
            ->whereNotNull('published_at')
            ->where('published_at', '>=', $this->sentPostsRecentCutoffUtc())
            ->whereBetween('published_at', [$sinceStart, $untilEnd])
            ->with('user', 'page')
            ->get();

        foreach ($dbSentPosts as $dbPost) {
            $extId = (string) $dbPost->post_id;
            if ($extId === '' || isset($graphPostIds[$extId])) {
                continue;
            }
            $page = $pagesById->get($dbPost->account_id);
            if (! $page) {
                continue;
            }
            $graphPostIds[$extId] = true;
            $allPosts[] = $this->sentFacebookPostFromLocalRecord($dbPost, $page);
        }

        usort($allPosts, function ($a, $b) {
            $ta = $this->parseCreatedTime($a['created_time'] ?? null);
            $tb = $this->parseCreatedTime($b['created_time'] ?? null);

            return $tb - $ta;
        });

        $isFetching = empty($allPosts);
        $fetchingMessage = $isFetching ? 'Posts for this page are being fetched. Please check back shortly.' : null;
        $total = count($allPosts);
        if ($limit !== null) {
            $pagedPosts = array_slice($allPosts, $offset, $limit);
            $nextOffset = $offset + count($pagedPosts);

            return response()->json([
                'success' => true,
                'posts' => $pagedPosts,
                'posts_fetching' => $isFetching,
                'posts_fetching_message' => $fetchingMessage,
                'source' => $source,
                'total' => $total,
                'has_more' => $nextOffset < $total,
                'next_offset' => $nextOffset,
            ]);
        }

        return response()->json([
            'success' => true,
            'posts' => $allPosts,
            'posts_fetching' => $isFetching,
            'posts_fetching_message' => $fetchingMessage,
            'source' => $source,
            'total' => $total,
            'has_more' => false,
            'next_offset' => $total,
        ]);
    }

    /**
     * Pinterest Sent tab: pins from `pinterest_pins` (synced board feed) merged with scheduled `posts`
     * not present in that snapshot — same pattern as Facebook sent page + `facebook_posts`.
     */
    public function getPinterestSentPosts(Request $request)
    {
        $userId = (int) Auth::id();
        $viewer = Auth::guard('user')->user();
        $postCreatorIds = $viewer instanceof User ? $viewer->schedulePostCreatorUserIds() : [$userId];
        $offset = max(0, (int) $request->input('offset', 0));
        $limitInput = $request->input('limit');
        $limit = is_null($limitInput) ? null : max(1, min(50, (int) $limitInput));

        $accountIds = (array) $request->input('account_id', []);
        if (empty($accountIds)) {
            return response()->json(['success' => false, 'message' => 'No account selected', 'posts' => []]);
        }

        $boards = Board::with('pinterest')->whereIn('id', $accountIds)->get();
        if ($boards->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'No boards found', 'posts' => []]);
        }

        $boardIds = $boards->pluck('id')->all();
        $boardsById = $boards->keyBy('id');

        $until = now()->format('Y-m-d');
        $since = now()->subYear()->format('Y-m-d');
        $duration = 'full_year';

        $allPosts = [];
        $pinIdsSeen = [];

        foreach ($boards as $board) {
            $cacheKey = $this->pinterestSentPostsCacheKey($userId, (int) $board->id, $duration, $since, $until);
            $posts = null;
            try {
                $posts = Cache::get($cacheKey);
            } catch (\Throwable $e) {
                Log::warning('Pinterest sent posts cache read failed', [
                    'key' => $cacheKey,
                    'message' => $e->getMessage(),
                ]);
            }
            if ($posts === null) {
                $posts = $this->fetchPinterestPinsFromStore($board, $since, $until, $duration);
                if ($posts !== null) {
                    try {
                        Cache::put($cacheKey, $posts, now()->addHours(self::POSTS_CACHE_TTL_HOURS));
                    } catch (\Throwable $e) {
                        Log::warning('Pinterest sent posts cache write failed', [
                            'key' => $cacheKey,
                            'message' => $e->getMessage(),
                        ]);
                    }
                }
            }
            if (! is_array($posts)) {
                $posts = [];
            }

            $postIds = collect($posts)
                ->pluck('id')
                ->filter()
                ->map(fn ($id) => (string) $id)
                ->unique()
                ->values()
                ->all();

            $ourPostsByExternalId = empty($postIds)
                ? collect()
                : Post::withoutGlobalScopes()
                    ->whereIn('user_id', $postCreatorIds)
                    ->whereIn('post_id', $postIds)
                    ->with('user')
                    ->get()
                    ->keyBy(fn ($p) => (string) $p->post_id);

            foreach ($posts as &$post) {
                $post['account_name'] = $board->name;
                $post['account_profile'] = $board->pinterest?->profile_image ?? '';
                $post['social_type'] = 'pinterest';
                $post['page_db_id'] = $board->id;
                $ourPost = $ourPostsByExternalId->get((string) ($post['id'] ?? ''));
                if ($ourPost) {
                    $post['db_post_id'] = $ourPost->id;
                    if ($ourPost->user) {
                        $post['publisher_username'] = $ourPost->user->username ?? $ourPost->user->full_name ?? $ourPost->user->email ?? '';
                        $post['publisher_email'] = $ourPost->user->email ?? '';
                    }
                }
            }
            unset($post);

            foreach ($posts as $p) {
                $pid = (string) ($p['id'] ?? '');
                if ($pid !== '') {
                    $pinIdsSeen[$pid] = true;
                }
            }

            $allPosts = array_merge($allPosts, $posts);
        }

        $sinceStart = Carbon::parse($since)->startOfDay();
        $untilEnd = Carbon::parse($until)->endOfDay();
        $dbSentPosts = Post::withoutGlobalScopes()
            ->whereIn('user_id', $postCreatorIds)
            ->where('social_type', 'like', '%pinterest%')
            ->whereIn('account_id', $boardIds)
            ->where('status', 1)
            ->whereNotNull('post_id')
            ->whereNotNull('published_at')
            ->where('published_at', '>=', $this->sentPostsRecentCutoffUtc())
            ->whereBetween('published_at', [$sinceStart, $untilEnd])
            ->where('source', 'schedule')
            ->with('user', 'board.pinterest')
            ->get();

        foreach ($dbSentPosts as $dbPost) {
            $extId = (string) $dbPost->post_id;
            if ($extId === '' || isset($pinIdsSeen[$extId])) {
                continue;
            }
            $board = $boardsById->get($dbPost->account_id) ?? $dbPost->board;
            if (! $board) {
                continue;
            }
            $pinIdsSeen[$extId] = true;
            $allPosts[] = $this->sentTabPostFromPinterestRecord($dbPost, $board);
        }

        usort($allPosts, function ($a, $b) {
            $ta = $this->parseCreatedTime($a['created_time'] ?? null);
            $tb = $this->parseCreatedTime($b['created_time'] ?? null);

            return $tb - $ta;
        });

        $isFetching = empty($allPosts);
        $fetchingMessage = $isFetching ? 'Pins for this board are being fetched. Please check back shortly.' : null;
        $total = count($allPosts);

        if ($limit !== null) {
            $pagedPosts = array_slice($allPosts, $offset, $limit);
            $nextOffset = $offset + count($pagedPosts);

            return response()->json([
                'success' => true,
                'posts' => $pagedPosts,
                'posts_fetching' => $isFetching,
                'posts_fetching_message' => $fetchingMessage,
                'total' => $total,
                'has_more' => $nextOffset < $total,
                'next_offset' => $nextOffset,
            ]);
        }

        return response()->json([
            'success' => true,
            'posts' => $allPosts,
            'posts_fetching' => $isFetching,
            'posts_fetching_message' => $fetchingMessage,
            'total' => $total,
            'has_more' => false,
            'next_offset' => $total,
        ]);
    }

    /**
     * TikTok Sent tab: synced `tiktok_posts` metrics merged with published schedule posts.
     */
    public function getTikTokSentPosts(Request $request)
    {
        $userId = (int) Auth::id();
        $viewer = Auth::guard('user')->user();
        $postCreatorIds = $viewer instanceof User ? $viewer->schedulePostCreatorUserIds() : [$userId];
        $accountIds = (array) $request->input('account_id', []);
        if (empty($accountIds)) {
            return response()->json(['success' => false, 'message' => 'No account selected', 'posts' => []]);
        }

        $accounts = Tiktok::whereIn('id', $accountIds)->get();
        if ($accounts->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'No TikTok accounts found', 'posts' => []]);
        }

        $tiktokIds = $accounts->pluck('id')->all();
        $accountsById = $accounts->keyBy('id');

        $cardsByKey = [];
        $tiktokPostsByAccount = TiktokPost::query()
            ->whereIn('tiktok_id', $tiktokIds)
            ->orderByDesc('post_created_date')
            ->get()
            ->groupBy('tiktok_id');

        foreach ($accounts as $account) {
            $rows = $tiktokPostsByAccount->get($account->id, collect());
            foreach ($rows as $row) {
                if (! $row instanceof TiktokPost) {
                    continue;
                }
                $videoId = (string) $row->tiktok_video_id;
                if ($videoId === '') {
                    continue;
                }
                $cardsByKey['video:'.$videoId] = $this->sentTabPostFromTiktokVideo($row, $account);
            }
        }

        $dbPosts = Post::withoutGlobalScopes()
            ->whereIn('user_id', $postCreatorIds)
            ->where('social_type', 'like', '%tiktok%')
            ->whereIn('account_id', $tiktokIds)
            ->where('status', 1)
            ->whereNotNull('published_at')
            ->where('source', 'schedule')
            ->with('user', 'tiktok')
            ->orderByDesc('published_at')
            ->get();

        foreach ($dbPosts as $dbPost) {
            $account = $accountsById->get($dbPost->account_id) ?? $dbPost->tiktok;
            if (! $account instanceof Tiktok) {
                continue;
            }

            $accountVideos = $tiktokPostsByAccount->get($account->id, collect());
            $matchedVideo = $this->findTiktokPostForSchedulePost($dbPost, $accountVideos);
            $videoId = $matchedVideo ? (string) $matchedVideo->tiktok_video_id : '';
            $cardKey = $videoId !== '' ? 'video:'.$videoId : 'db:'.$dbPost->id;

            if ($videoId !== '' && isset($cardsByKey['video:'.$videoId])) {
                $cardsByKey['video:'.$videoId] = $this->mergeScheduleMetaIntoTiktokSentCard(
                    $cardsByKey['video:'.$videoId],
                    $dbPost
                );
                continue;
            }

            if (! isset($cardsByKey[$cardKey])) {
                $cardsByKey[$cardKey] = $this->sentTabPostFromTikTokRecord($dbPost, $account, $matchedVideo);
            }
        }

        $allPosts = array_values($cardsByKey);
        usort($allPosts, function ($a, $b) {
            $ta = $this->parseCreatedTime($a['created_time'] ?? null);
            $tb = $this->parseCreatedTime($b['created_time'] ?? null);

            return $tb - $ta;
        });

        $isFetching = empty($allPosts) && TiktokPost::whereIn('tiktok_id', $tiktokIds)->doesntExist();

        return response()->json([
            'success' => true,
            'posts' => $allPosts,
            'posts_fetching' => $isFetching,
            'posts_fetching_message' => $isFetching
                ? 'TikTok videos for this account are being fetched. Please check back shortly.'
                : null,
            'total' => count($allPosts),
        ]);
    }

    /**
     * Instagram Sent tab: synced `instagram_posts` (Meta media + insights) merged with published schedule posts.
     */
    public function getInstagramSentPosts(Request $request)
    {
        $userId = (int) Auth::id();
        $viewer = Auth::guard('user')->user();
        $postCreatorIds = $viewer instanceof User ? $viewer->schedulePostCreatorUserIds() : [$userId];
        $accountIds = (array) $request->input('account_id', []);
        if (empty($accountIds)) {
            return response()->json(['success' => false, 'message' => 'No account selected', 'posts' => []]);
        }

        $ownerId = (int) ($viewer instanceof User ? ($viewer->getEffectiveUser()?->id ?? $viewer->id) : Auth::guard('user')->id());
        $accounts = InstagramAccount::query()
            ->whereIn('id', $accountIds)
            ->where('user_id', $ownerId)
            ->get();
        if ($accounts->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'No Instagram accounts found', 'posts' => []]);
        }

        $igIds = $accounts->pluck('id')->all();
        $accountsById = $accounts->keyBy('id');

        $cardsByKey = [];
        $instagramPostsByAccount = InstagramPost::query()
            ->whereIn('instagram_account_id', $igIds)
            ->orderByDesc('post_created_date')
            ->get()
            ->groupBy('instagram_account_id');

        foreach ($accounts as $account) {
            $rows = $instagramPostsByAccount->get($account->id, collect());
            foreach ($rows as $row) {
                if (! $row instanceof InstagramPost) {
                    continue;
                }
                $mediaId = (string) $row->ig_media_id;
                if ($mediaId === '') {
                    continue;
                }
                $cardsByKey['media:'.$mediaId] = $this->sentTabPostFromInstagramMedia($row, $account);
            }
        }

        $dbPosts = Post::withoutGlobalScopes()
            ->whereIn('user_id', $postCreatorIds)
            ->where('social_type', 'like', '%instagram%')
            ->whereIn('account_id', $igIds)
            ->where('status', 1)
            ->whereNotNull('published_at')
            ->where('source', 'schedule')
            ->with('user', 'instagramAccount')
            ->orderByDesc('published_at')
            ->get();

        foreach ($dbPosts as $dbPost) {
            $account = $accountsById->get($dbPost->account_id) ?? $dbPost->instagramAccount;
            if (! $account instanceof InstagramAccount) {
                continue;
            }

            $accountMedia = $instagramPostsByAccount->get($account->id, collect());
            $matchedMedia = $this->findInstagramPostForSchedulePost($dbPost, $accountMedia);
            $mediaId = $matchedMedia ? (string) $matchedMedia->ig_media_id : '';
            $cardKey = $mediaId !== '' ? 'media:'.$mediaId : 'db:'.$dbPost->id;

            if ($mediaId !== '' && isset($cardsByKey['media:'.$mediaId])) {
                $cardsByKey['media:'.$mediaId] = $this->mergeScheduleMetaIntoInstagramSentCard(
                    $cardsByKey['media:'.$mediaId],
                    $dbPost
                );

                continue;
            }

            if (! isset($cardsByKey[$cardKey])) {
                $cardsByKey[$cardKey] = $this->sentTabPostFromInstagramRecord($dbPost, $account, $matchedMedia);
            }
        }

        $allPosts = array_values($cardsByKey);
        usort($allPosts, function ($a, $b) {
            $ta = $this->parseCreatedTime($a['created_time'] ?? null);
            $tb = $this->parseCreatedTime($b['created_time'] ?? null);

            return $tb - $ta;
        });

        $isFetching = empty($allPosts) && InstagramPost::whereIn('instagram_account_id', $igIds)->doesntExist();

        return response()->json([
            'success' => true,
            'posts' => $allPosts,
            'posts_fetching' => $isFetching,
            'posts_fetching_message' => $isFetching
                ? 'Instagram posts for this account are being fetched. Please check back shortly.'
                : null,
            'total' => count($allPosts),
        ]);
    }

    /**
     * Threads Sent tab: published schedule posts for selected accounts.
     */
    public function getThreadsSentPosts(Request $request)
    {
        $userId = (int) Auth::id();
        $viewer = Auth::guard('user')->user();
        $postCreatorIds = $viewer instanceof User ? $viewer->schedulePostCreatorUserIds() : [(int) Auth::guard('user')->id()];
        $accountIds = (array) $request->input('account_id', []);
        if (empty($accountIds)) {
            return response()->json(['success' => false, 'message' => 'No account selected', 'posts' => []]);
        }

        $ownerId = (int) ($viewer instanceof User ? ($viewer->getEffectiveUser()?->id ?? $viewer->id) : Auth::guard('user')->id());
        $accounts = Thread::query()
            ->whereIn('id', $accountIds)
            ->where('user_id', $ownerId)
            ->get();
        if ($accounts->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'No Threads accounts found', 'posts' => []]);
        }

        $accountsById = $accounts->keyBy('id');
        $allPosts = [];
        $graphPostIds = [];

        // Sent tab (beta): full_year only — since = 1 year ago, until = today
        $until = now()->format('Y-m-d');
        $since = now()->subYear()->format('Y-m-d');
        $duration = 'full_year';

        foreach ($accounts as $account) {
            $cacheKey = $this->sentPostsCacheKey($userId, (int) $account->id, $duration, $since, $until);
            $posts = null;
            try {
                $posts = Cache::get($cacheKey);
            } catch (\Throwable $e) {
                Log::warning('Threads sent posts cache read failed', [
                    'key' => $cacheKey,
                    'message' => $e->getMessage(),
                ]);
            }
            if ($posts === null) {
                $posts = $this->fetchThreadPostsFromStore($account, $since, $until, $duration);
                if ($posts !== null) {
                    try {
                        Cache::put($cacheKey, $posts, now()->addHours(self::POSTS_CACHE_TTL_HOURS));
                    } catch (\Throwable $e) {
                        Log::warning('Threads sent posts cache write failed', [
                            'key' => $cacheKey,
                            'message' => $e->getMessage(),
                        ]);
                    }
                }
            }
            if (! is_array($posts)) {
                $posts = [];
            }

            $postIds = collect($posts)
                ->pluck('id')
                ->filter()
                ->map(fn($id) => (string) $id)
                ->unique()
                ->values()
                ->all();

            $ourPostsByExternalId = empty($postIds)
                ? collect()
                : Post::withoutGlobalScopes()
                ->whereIn('user_id', $postCreatorIds)
                ->whereIn('post_id', $postIds)
                ->with('user')
                ->get()
                ->keyBy(fn($p) => (string) $p->post_id);

            foreach ($posts as &$post) {
                $post['account_name'] = $account->username ? '@' . $account->username : 'Threads';
                $post['account_profile'] = $account->profile_image ?? '';
                $post['social_type'] = 'threads';
                $post['page_db_id'] = $account->id;
                $ourPost = $ourPostsByExternalId->get((string) ($post['id'] ?? ''));
                if ($ourPost) {
                    $post['db_post_id'] = $ourPost->id;
                    if ($ourPost->user) {
                        $post['publisher_username'] = $ourPost->user->username ?? $ourPost->user->full_name ?? $ourPost->user->email ?? '';
                        $post['publisher_email'] = $ourPost->user->email ?? '';
                    }
                }
            }
            unset($post);

            foreach ($posts as $p) {
                if (! empty($p['id'])) {
                    $graphPostIds[(string) $p['id']] = true;
                }
            }

            $allPosts = array_merge($allPosts, $posts);
        }

        // Add app-published records that are not yet present in snapshot/API data.
        $sinceStart = Carbon::parse($since)->startOfDay();
        $untilEnd = Carbon::parse($until)->endOfDay();
        $threadIds = $accounts->pluck('id')->all();
        $dbSentPosts = Post::withoutGlobalScopes()
            ->whereIn('user_id', $postCreatorIds)
            ->where(function ($q) {
                $q->where('social_type', 'like', '%threads%')
                    ->orWhere('social_type', 'like', '%thread%');
            })
            ->whereIn('account_id', $threadIds)
            ->where('status', 1)
            ->whereNotNull('post_id')
            ->whereNotNull('published_at')
            ->where('published_at', '>=', $this->sentPostsRecentCutoffUtc())
            ->whereBetween('published_at', [$sinceStart, $untilEnd])
            ->with('user', 'thread')
            ->get();

        foreach ($dbSentPosts as $dbPost) {
            $extId = (string) $dbPost->post_id;
            if ($extId === '' || isset($graphPostIds[$extId])) {
                continue;
            }
            $account = $accountsById->get($dbPost->account_id);
            if (! $account instanceof Thread) {
                continue;
            }
            $graphPostIds[$extId] = true;
            $allPosts[] = $this->sentTabPostFromThreadsRecord($dbPost, $account);
        }

        usort($allPosts, function ($a, $b) {
            $ta = $this->parseCreatedTime($a['created_time'] ?? null);
            $tb = $this->parseCreatedTime($b['created_time'] ?? null);

            return $tb - $ta;
        });

        return response()->json(['success' => true, 'posts' => $allPosts]);
    }

    /**
     * LinkedIn Sent tab: published schedule posts for selected accounts.
     */
    public function getLinkedInSentPosts(Request $request)
    {
        $viewer = Auth::guard('user')->user();
        $postCreatorIds = $viewer instanceof User ? $viewer->schedulePostCreatorUserIds() : [(int) Auth::guard('user')->id()];
        $accountIds = (array) $request->input('account_id', []);
        if (empty($accountIds)) {
            return response()->json(['success' => false, 'message' => 'No account selected', 'posts' => []]);
        }

        $ownerId = (int) ($viewer instanceof User ? ($viewer->getEffectiveUser()?->id ?? $viewer->id) : Auth::guard('user')->id());
        $accounts = Linkedin::query()
            ->whereIn('id', $accountIds)
            ->where('user_id', $ownerId)
            ->get();
        if ($accounts->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'No LinkedIn accounts found', 'posts' => []]);
        }

        $accountIds = $accounts->pluck('id')->all();
        $accountsById = $accounts->keyBy('id');

        $dbPosts = Post::withoutGlobalScopes()
            ->whereIn('user_id', $postCreatorIds)
            ->where('social_type', 'like', '%linkedin%')
            ->whereIn('account_id', $accountIds)
            ->where('status', 1)
            ->whereNotNull('published_at')
            ->where('source', 'schedule')
            ->with('user', 'linkedin')
            ->orderByDesc('published_at')
            ->get();

        $allPosts = [];
        foreach ($dbPosts as $dbPost) {
            $account = $accountsById->get($dbPost->account_id) ?? $dbPost->linkedin;
            if (! $account instanceof Linkedin) {
                continue;
            }
            $allPosts[] = $this->sentTabPostFromLinkedInRecord($dbPost, $account);
        }

        return response()->json(['success' => true, 'posts' => $allPosts]);
    }

    /**
     * Sent-tab payload from a synced `instagram_posts` row (Meta media insights).
     */
    private function sentTabPostFromInstagramMedia(InstagramPost $media, InstagramAccount $account): array
    {
        $post = $media->toAnalyticsPostArray();
        $insights = $this->sentTabInsightsFromInstagramPost($media);

        $rawCreated = $post['created_time'] ?? $media->post_created_date;
        if ($rawCreated instanceof \DateTimeInterface) {
            $createdTime = $rawCreated->format(\DateTimeInterface::ATOM);
        } else {
            $createdTime = is_string($rawCreated) ? $rawCreated : '';
        }

        $mediaType = strtolower((string) ($post['media_type'] ?? $media->media_type ?? ''));
        $postType = match ($mediaType) {
            'video', 'reels', 'reel' => 'video',
            'carousel_album', 'carousel' => 'carousel',
            'story', 'stories' => 'story',
            default => 'photo',
        };

        $videoUrl = '';
        if (in_array($mediaType, ['video', 'reels', 'reel'], true)) {
            $videoUrl = (string) ($post['media_url'] ?? '');
        }

        $username = ltrim((string) ($account->username ?? ''), '@');

        return [
            'id' => (string) ($post['id'] ?? $media->ig_media_id),
            'created_time' => $createdTime,
            'message' => (string) ($post['message'] ?? $post['caption'] ?? ''),
            'story' => '',
            'type' => $postType,
            'media_type' => $mediaType,
            'full_picture' => (string) ($post['full_picture'] ?? $post['thumbnail_url'] ?? ''),
            'carousel_items' => [],
            'permalink_url' => $post['permalink_url'] ?? $media->permalink_url,
            'account_name' => $account->name ?: ($username !== '' ? '@'.$username : 'Instagram'),
            'account_profile' => (string) ($account->profile_image ?? ''),
            'social_type' => 'instagram',
            'page_db_id' => $account->id,
            'insights' => $insights,
            'comments' => (int) ($insights['post_comments'] ?? 0),
            'shares' => (int) ($insights['post_shares'] ?? 0),
            'video_url' => $videoUrl,
        ];
    }

    /**
     * Minimal Sent-tab payload from a published Instagram Post row (same keys as Pinterest / Facebook sent cards).
     */
    private function sentTabPostFromInstagramRecord(Post $dbPost, InstagramAccount $account, ?InstagramPost $matchedMedia = null): array
    {
        if ($matchedMedia) {
            $payload = $this->sentTabPostFromInstagramMedia($matchedMedia, $account);

            return $this->mergeScheduleMetaIntoInstagramSentCard($payload, $dbPost);
        }

        $published = Carbon::parse($dbPost->published_at);
        $createdTime = $published->toIso8601String();

        $fullPicture = '';
        $rawImage = $dbPost->getAttributes()['image'] ?? null;
        if (! empty($rawImage)) {
            $img = (string) $rawImage;
            if (str_starts_with($img, 'http://') || str_starts_with($img, 'https://')) {
                $fullPicture = $img;
            } else {
                $fullPicture = url(getImage('', $img));
            }
        }
        if ($fullPicture === '' && strtolower((string) ($dbPost->getAttributes()['type'] ?? '')) === 'carousel') {
            $fullPicture = (string) ($this->instagramCarouselFirstStillPreviewUrl($dbPost) ?? '');
        }

        $videoUrl = $this->postStoredVideoUrl($dbPost);

        $usernameRaw = (string) ($account->username ?? '');
        $username = ltrim($usernameRaw, '@');
        $permalink = $dbPost->post_id
            ? ('https://www.instagram.com/p/'.rawurlencode((string) $dbPost->post_id).'/')
            : ($username !== '' ? 'https://www.instagram.com/'.rawurlencode($username).'/' : null);

        $profileImage = (string) ($account->profile_image ?? '');

        $payload = [
            'id' => $dbPost->post_id ? (string) $dbPost->post_id : ('db-'.$dbPost->id),
            'created_time' => $createdTime,
            'message' => (string) ($dbPost->title ?? ''),
            'story' => '',
            'type' => (string) ($dbPost->getAttributes()['type'] ?? $dbPost->type ?? ''),
            'full_picture' => $fullPicture,
            'carousel_items' => $this->instagramCarouselGalleryItemsFromPost($dbPost),
            'permalink_url' => $permalink,
            'account_name' => $account->name ?: ($username !== '' ? '@'.$username : 'Instagram'),
            'account_profile' => $profileImage,
            'social_type' => 'instagram',
            'page_db_id' => $account->id,
            'db_post_id' => $dbPost->id,
            'insights' => $this->sentTabInsightsFromInstagramPost(null),
            'comments' => 0,
            'shares' => 0,
            'from_local_db' => true,
            'video_url' => $videoUrl,
        ];

        if ($dbPost->user) {
            $payload['publisher_username'] = $dbPost->user->username ?? $dbPost->user->full_name ?? $dbPost->user->email ?? '';
            $payload['publisher_email'] = $dbPost->user->email ?? '';
        }

        return $payload;
    }

    /**
     * @return array<string, int>
     */
    private function sentTabInsightsFromInstagramPost(?InstagramPost $media): array
    {
        if (! $media) {
            return [
                'post_reactions' => 0,
                'post_impressions' => 0,
                'post_reach' => 0,
                'post_comments' => 0,
                'post_saves' => 0,
                'post_shares' => 0,
                'post_clicks' => 0,
            ];
        }

        $insights = is_array($media->post_insights) ? $media->post_insights : [];
        $likes = (int) ($insights['post_reactions'] ?? $media->likes_count ?? 0);
        $comments = (int) ($insights['post_comments'] ?? $media->comments_count ?? 0);
        $impressions = (int) ($insights['post_impressions'] ?? $media->impressions_count ?? 0);
        $reach = (int) ($insights['post_reach'] ?? $media->reach_count ?? 0);
        $saves = (int) ($insights['post_saves'] ?? $media->saves_count ?? 0);
        $shares = (int) ($insights['post_shares'] ?? $media->shares_count ?? 0);

        return [
            'post_reactions' => $likes,
            'post_impressions' => $impressions,
            'post_reach' => $reach,
            'post_comments' => $comments,
            'post_saves' => $saves,
            'post_shares' => $shares,
            'post_clicks' => $saves,
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, InstagramPost>  $accountMedia
     */
    private function findInstagramPostForSchedulePost(Post $dbPost, $accountMedia): ?InstagramPost
    {
        if ($accountMedia->isEmpty()) {
            return null;
        }

        $publishId = trim((string) ($dbPost->post_id ?? ''));
        if ($publishId !== '') {
            $byMediaId = $accountMedia->firstWhere('ig_media_id', $publishId);
            if ($byMediaId instanceof InstagramPost) {
                return $byMediaId;
            }
        }

        $scheduleCaption = $this->normalizeInstagramMatchText((string) ($dbPost->title ?? ''));
        $publishedAt = $dbPost->published_at ? Carbon::parse($dbPost->published_at) : null;

        $best = null;
        $bestScore = PHP_INT_MAX;

        foreach ($accountMedia as $media) {
            if (! $media instanceof InstagramPost) {
                continue;
            }

            $mediaCaption = $this->normalizeInstagramMatchText(
                (string) (is_array($media->post_data) ? ($media->post_data['caption'] ?? $media->post_data['message'] ?? '') : '')
            );

            if ($scheduleCaption !== '' && $mediaCaption !== '') {
                if ($scheduleCaption !== $mediaCaption
                    && ! str_contains($mediaCaption, $scheduleCaption)
                    && ! str_contains($scheduleCaption, $mediaCaption)) {
                    continue;
                }
            }

            $created = $media->post_created_date;
            if ($publishedAt && $created) {
                $diffHours = abs($publishedAt->diffInHours($created));
                if ($diffHours > 168) {
                    continue;
                }
                if ($diffHours < $bestScore) {
                    $bestScore = $diffHours;
                    $best = $media;
                }
            } elseif ($best === null) {
                $best = $media;
            }
        }

        return $best;
    }

    private function normalizeInstagramMatchText(string $text): string
    {
        $text = mb_strtolower(trim($text));
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return $text;
    }

    /**
     * @param  array<string, mixed>  $card
     * @return array<string, mixed>
     */
    private function mergeScheduleMetaIntoInstagramSentCard(array $card, Post $dbPost): array
    {
        $card['db_post_id'] = $dbPost->id;
        $card['from_local_db'] = true;

        $videoUrl = $this->postStoredVideoUrl($dbPost);
        if ($videoUrl !== null && $videoUrl !== '') {
            $card['video_url'] = $videoUrl;
        }

        if (empty($card['carousel_items'])) {
            $card['carousel_items'] = $this->instagramCarouselGalleryItemsFromPost($dbPost);
        }

        if (empty($card['full_picture'])) {
            $postType = strtolower((string) ($dbPost->getAttributes()['type'] ?? $dbPost->type ?? ''));
            $rawImage = $dbPost->getAttributes()['image'] ?? null;
            if (! empty($rawImage)) {
                $img = (string) $rawImage;
                if (str_starts_with($img, 'http://') || str_starts_with($img, 'https://')) {
                    $card['full_picture'] = $img;
                } else {
                    $card['full_picture'] = url(getImage('', $img));
                }
            } elseif ($postType === 'carousel') {
                $card['full_picture'] = (string) ($this->instagramCarouselFirstStillPreviewUrl($dbPost) ?? '');
            }
        }

        if ($dbPost->user) {
            $card['publisher_username'] = $dbPost->user->username ?? $dbPost->user->full_name ?? $dbPost->user->email ?? '';
            $card['publisher_email'] = $dbPost->user->email ?? '';
        }

        return $card;
    }

    /**
     * Minimal Sent-tab payload from a published Threads Post row.
     */
    private function sentTabPostFromThreadsRecord(Post $dbPost, Thread $account): array
    {
        $published = Carbon::parse($dbPost->published_at);
        $createdTime = $published->toIso8601String();

        $fullPicture = '';
        $rawImage = $dbPost->getAttributes()['image'] ?? null;
        if (! empty($rawImage)) {
            $img = (string) $rawImage;
            if (str_starts_with($img, 'http://') || str_starts_with($img, 'https://')) {
                $fullPicture = $img;
            } else {
                $fullPicture = url(getImage('', $img));
            }
        }

        $videoUrl = $this->postStoredVideoUrl($dbPost);
        $username = ltrim((string) ($account->username ?? ''), '@');
        $permalink = $username !== ''
            ? 'https://www.threads.com/@' . rawurlencode($username)
            : null;

        $payload = [
            'id' => $dbPost->post_id ? (string) $dbPost->post_id : ('db-' . $dbPost->id),
            'created_time' => $createdTime,
            'message' => (string) ($dbPost->title ?? ''),
            'story' => '',
            'type' => (string) ($dbPost->getAttributes()['type'] ?? $dbPost->type ?? ''),
            'full_picture' => $fullPicture,
            'carousel_items' => $this->instagramCarouselGalleryItemsFromPost($dbPost),
            'permalink_url' => $permalink,
            'account_name' => $username !== '' ? '@' . $username : 'Threads',
            'account_profile' => (string) ($account->profile_image ?? ''),
            'social_type' => 'threads',
            'page_db_id' => $account->id,
            'db_post_id' => $dbPost->id,
            'insights' => [
                'post_reactions' => 0,
                'post_impressions' => 0,
                'post_shares' => 0,
                'post_comments' => 0,
            ],
        ];

        if ($videoUrl !== null) {
            $payload['video_url'] = $videoUrl;
        }

        return $payload;
    }

    /**
     * Minimal Sent-tab payload from a published LinkedIn Post row.
     */
    private function sentTabPostFromLinkedInRecord(Post $dbPost, Linkedin $account): array
    {
        $published = Carbon::parse($dbPost->published_at);
        $createdTime = $published->toIso8601String();

        $fullPicture = '';
        $rawImage = $dbPost->getAttributes()['image'] ?? null;
        if (! empty($rawImage)) {
            $img = (string) $rawImage;
            if (str_starts_with($img, 'http://') || str_starts_with($img, 'https://')) {
                $fullPicture = $img;
            } else {
                $fullPicture = url(getImage('', $img));
            }
        }

        $videoUrl = $this->postStoredVideoUrl($dbPost);
        $username = ltrim((string) ($account->username ?? ''), '@');
        $permalink = $username !== ''
            ? 'https://www.linkedin.com/in/' . rawurlencode($username)
            : null;

        $payload = [
            'id' => $dbPost->post_id ? (string) $dbPost->post_id : ('db-' . $dbPost->id),
            'created_time' => $createdTime,
            'message' => (string) ($dbPost->title ?? ''),
            'story' => '',
            'type' => (string) ($dbPost->getAttributes()['type'] ?? $dbPost->type ?? ''),
            'full_picture' => $fullPicture,
            'permalink_url' => $permalink,
            'account_name' => $username !== '' ? '@' . $username : 'LinkedIn',
            'account_profile' => (string) ($account->profile_image ?? ''),
            'social_type' => 'linkedin',
            'page_db_id' => $account->id,
            'db_post_id' => $dbPost->id,
            'insights' => [
                'post_reactions' => 0,
                'post_impressions' => 0,
                'post_clicks' => 0,
            ],
            'comments' => 0,
            'shares' => 0,
            'from_local_db' => true,
            'video_url' => $videoUrl,
        ];

        if ($dbPost->user) {
            $payload['publisher_username'] = $dbPost->user->username ?? $dbPost->user->full_name ?? $dbPost->user->email ?? '';
            $payload['publisher_email'] = $dbPost->user->email ?? '';
        }

        return $payload;
    }

    /**
     * Sent-tab payload from a synced `tiktok_posts` row (TikTok API metrics).
     */
    private function sentTabPostFromTiktokVideo(TiktokPost $video, Tiktok $account): array
    {
        $post = $video->toAnalyticsPostArray();
        $insights = $this->sentTabInsightsFromTiktokPost($video);

        $rawCreated = $post['created_time'] ?? $video->post_created_date;
        if ($rawCreated instanceof \DateTimeInterface) {
            $createdTime = $rawCreated->format(\DateTimeInterface::ATOM);
        } else {
            $createdTime = is_string($rawCreated) ? $rawCreated : '';
        }

        $username = (string) ($account->username ?? '');

        return [
            'id' => (string) ($post['id'] ?? $video->tiktok_video_id),
            'created_time' => $createdTime,
            'message' => (string) ($post['message'] ?? $post['title'] ?? $video->title ?? ''),
            'story' => '',
            'type' => (string) ($post['type'] ?? 'video'),
            'full_picture' => (string) ($post['full_picture'] ?? ''),
            'permalink_url' => $post['permalink_url'] ?? $video->share_url,
            'account_name' => $account->display_name ?: $username ?: 'TikTok',
            'account_profile' => $account->profile_image ?? '',
            'social_type' => 'tiktok',
            'page_db_id' => $account->id,
            'insights' => $insights,
            'comments' => (int) ($insights['comment_count'] ?? 0),
            'shares' => (int) ($insights['share_count'] ?? 0),
            'video_url' => '',
        ];
    }

    /**
     * Minimal Sent-tab payload from a published TikTok Post row, optionally enriched from `tiktok_posts`.
     */
    private function sentTabPostFromTikTokRecord(Post $dbPost, Tiktok $account, ?TiktokPost $matchedVideo = null): array
    {
        if ($matchedVideo) {
            $payload = $this->sentTabPostFromTiktokVideo($matchedVideo, $account);
            $payload = $this->mergeScheduleMetaIntoTiktokSentCard($payload, $dbPost);

            return $payload;
        }

        $published = Carbon::parse($dbPost->published_at);
        $createdTime = $published->toIso8601String();

        $postType = strtolower((string) ($dbPost->getAttributes()['type'] ?? $dbPost->type ?? ''));
        $isVideo = $postType === 'video';

        $fullPicture = '';
        $rawImage = $dbPost->getAttributes()['image'] ?? null;
        if (! empty($rawImage)) {
            $img = $rawImage;
            if (str_starts_with($img, 'http://') || str_starts_with($img, 'https://')) {
                $fullPicture = $img;
            } elseif ($isVideo) {
                $fullPicture = fetchFromS3($img);
            } else {
                $fullPicture = url(getImage('', $img));
            }
        }

        $videoUrl = $this->postStoredVideoUrl($dbPost);

        $username = (string) ($account->username ?? '');
        $usernameForUrl = ltrim($username, '@');
        $permalink = $usernameForUrl !== ''
            ? 'https://www.tiktok.com/@'.rawurlencode($usernameForUrl)
            : null;

        $profileImage = $account->profile_image ?? '';

        $payload = [
            'id' => $dbPost->post_id ? (string) $dbPost->post_id : ('db-'.$dbPost->id),
            'created_time' => $createdTime,
            'message' => (string) ($dbPost->title ?? ''),
            'story' => '',
            'type' => (string) ($dbPost->type ?? ''),
            'full_picture' => $fullPicture,
            'permalink_url' => $permalink,
            'account_name' => $account->display_name ?: $username ?: 'TikTok',
            'account_profile' => $profileImage,
            'social_type' => 'tiktok',
            'page_db_id' => $account->id,
            'db_post_id' => $dbPost->id,
            'insights' => $this->sentTabInsightsFromTiktokPost(null),
            'comments' => 0,
            'shares' => 0,
            'from_local_db' => true,
            'video_url' => $videoUrl,
            'status' => (int) $dbPost->status,
            'response' => $dbPost->response,
        ];

        if ($dbPost->user) {
            $payload['publisher_username'] = $dbPost->user->username ?? $dbPost->user->full_name ?? $dbPost->user->email ?? '';
            $payload['publisher_email'] = $dbPost->user->email ?? '';
        }

        return $payload;
    }

    /**
     * @return array<string, int>
     */
    private function sentTabInsightsFromTiktokPost(?TiktokPost $video): array
    {
        if (! $video) {
            return [
                'view_count' => 0,
                'like_count' => 0,
                'comment_count' => 0,
                'share_count' => 0,
                'post_reactions' => 0,
                'post_impressions' => 0,
                'post_clicks' => 0,
            ];
        }

        $insights = is_array($video->post_insights) ? $video->post_insights : [];
        $views = (int) ($insights['view_count'] ?? $video->view_count ?? 0);
        $likes = (int) ($insights['like_count'] ?? $video->like_count ?? 0);
        $comments = (int) ($insights['comment_count'] ?? $video->comment_count ?? 0);
        $shares = (int) ($insights['share_count'] ?? $video->share_count ?? 0);

        return [
            'view_count' => $views,
            'like_count' => $likes,
            'comment_count' => $comments,
            'share_count' => $shares,
            'post_reactions' => $likes,
            'post_impressions' => $views,
            'post_clicks' => 0,
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, TiktokPost>  $accountVideos
     */
    private function findTiktokPostForSchedulePost(Post $dbPost, $accountVideos): ?TiktokPost
    {
        if ($accountVideos->isEmpty()) {
            return null;
        }

        $publishId = (string) ($dbPost->post_id ?? '');
        if ($publishId !== '') {
            $byPublishId = $accountVideos->firstWhere('tiktok_video_id', $publishId);
            if ($byPublishId instanceof TiktokPost) {
                return $byPublishId;
            }
        }

        $scheduleTitle = $this->normalizeTiktokMatchText((string) ($dbPost->title ?? ''));
        $publishedAt = $dbPost->published_at ? Carbon::parse($dbPost->published_at) : null;

        $best = null;
        $bestScore = PHP_INT_MAX;

        foreach ($accountVideos as $video) {
            if (! $video instanceof TiktokPost) {
                continue;
            }

            $videoTitle = $this->normalizeTiktokMatchText((string) ($video->title ?? ''));
            if ($scheduleTitle !== '' && $videoTitle !== '') {
                if ($scheduleTitle !== $videoTitle
                    && ! str_contains($videoTitle, $scheduleTitle)
                    && ! str_contains($scheduleTitle, $videoTitle)) {
                    continue;
                }
            }

            $created = $video->resolvedCreatedAt();
            if ($publishedAt && $created) {
                $diffHours = abs($publishedAt->diffInHours($created));
                if ($diffHours > 168) {
                    continue;
                }
                if ($diffHours < $bestScore) {
                    $bestScore = $diffHours;
                    $best = $video;
                }
            } elseif ($best === null) {
                $best = $video;
            }
        }

        return $best;
    }

    private function normalizeTiktokMatchText(string $text): string
    {
        $text = mb_strtolower(trim($text));
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return $text;
    }

    /**
     * @param  array<string, mixed>  $card
     * @return array<string, mixed>
     */
    private function mergeScheduleMetaIntoTiktokSentCard(array $card, Post $dbPost): array
    {
        $card['db_post_id'] = $dbPost->id;
        $card['from_local_db'] = true;
        $card['status'] = (int) $dbPost->status;
        $card['response'] = $dbPost->response;

        $videoUrl = $this->postStoredVideoUrl($dbPost);
        if ($videoUrl !== null && $videoUrl !== '') {
            $card['video_url'] = $videoUrl;
        }

        if (empty($card['full_picture'])) {
            $postType = strtolower((string) ($dbPost->getAttributes()['type'] ?? $dbPost->type ?? ''));
            $rawImage = $dbPost->getAttributes()['image'] ?? null;
            if (! empty($rawImage)) {
                $img = (string) $rawImage;
                if (str_starts_with($img, 'http://') || str_starts_with($img, 'https://')) {
                    $card['full_picture'] = $img;
                } elseif ($postType === 'video') {
                    $card['full_picture'] = fetchFromS3($img);
                } else {
                    $card['full_picture'] = url(getImage('', $img));
                }
            }
        }

        if ($dbPost->user) {
            $card['publisher_username'] = $dbPost->user->username ?? $dbPost->user->full_name ?? $dbPost->user->email ?? '';
            $card['publisher_email'] = $dbPost->user->email ?? '';
        }

        return $card;
    }

    /**
     * Sent-tab payload from a `pinterest_pins` row (metrics from stored pin analytics).
     */
    private function sentTabPostFromPinterestPin(PinterestPin $pin, Board $board): array
    {
        $post = $pin->toAnalyticsPostArray();
        $ins = is_array($post['insights'] ?? null) ? $post['insights'] : [];

        $reactions = (int) ($ins['pin_saves'] ?? $ins['post_reactions'] ?? 0);
        $impressions = (int) ($ins['post_impressions'] ?? 0);
        $clicks = (int) ($ins['post_clicks'] ?? 0);
        if ($clicks === 0) {
            $clicks = (int) ($ins['outbound_clicks'] ?? 0) + (int) ($ins['pin_clicks'] ?? 0);
        }

        $rawCreated = $post['created_time'] ?? $pin->pin_created_at;
        if ($rawCreated instanceof \DateTimeInterface) {
            $createdTime = $rawCreated->format(\DateTimeInterface::ATOM);
        } else {
            $createdTime = is_string($rawCreated) ? $rawCreated : '';
        }

        return [
            'id' => (string) ($post['id'] ?? $pin->pinterest_pin_id),
            'created_time' => $createdTime,
            'message' => (string) ($post['message'] ?? $post['title'] ?? ''),
            'story' => '',
            'full_picture' => (string) ($post['full_picture'] ?? ''),
            'permalink_url' => $post['permalink_url'] ?? null,
            'media_type' => (string) ($post['media_type'] ?? $post['type'] ?? ''),
            'type' => (string) ($post['type'] ?? $post['media_type'] ?? ''),
            'account_name' => $board->name,
            'account_profile' => $board->pinterest?->profile_image ?? '',
            'social_type' => 'pinterest',
            'page_db_id' => $board->id,
            'insights' => [
                'post_reactions' => $reactions,
                'post_impressions' => $impressions,
                'post_clicks' => $clicks,
            ],
            'comments' => (int) ($ins['total_comments'] ?? 0),
            'shares' => 0,
            'video_url' => '',
        ];
    }

    /**
     * Minimal Sent-tab payload from a published Pinterest Post row (metrics zeroed; same keys as Facebook sent cards).
     */
    private function sentTabPostFromPinterestRecord(Post $dbPost, Board $board): array
    {
        $published = Carbon::parse($dbPost->published_at);
        $createdTime = $published->toIso8601String();

        $fullPicture = '';
        if (! empty($dbPost->image)) {
            $img = $dbPost->image;
            if (str_starts_with($img, 'http://') || str_starts_with($img, 'https://')) {
                $fullPicture = $img;
            } else {
                $fullPicture = fetchFromS3($img);
            }
        }

        $pinId = $dbPost->post_id ? (string) $dbPost->post_id : '';
        $permalink = $pinId !== ''
            ? 'https://www.pinterest.com/pin/' . rawurlencode($pinId) . '/'
            : null;

        $profileImage = $board->pinterest?->profile_image ?? '';

        $payload = [
            'id' => $pinId !== '' ? $pinId : ('db-' . $dbPost->id),
            'created_time' => $createdTime,
            'message' => (string) ($dbPost->title ?? ''),
            'story' => '',
            'full_picture' => $fullPicture,
            'permalink_url' => $permalink,
            'account_name' => $board->name,
            'account_profile' => $profileImage,
            'social_type' => 'pinterest',
            'page_db_id' => $board->id,
            'db_post_id' => $dbPost->id,
            'insights' => [
                'post_reactions' => 0,
                'post_impressions' => 0,
                'post_clicks' => 0,
            ],
            'comments' => 0,
            'shares' => 0,
            'from_local_db' => true,
        ];

        $payload['type'] = (string) ($dbPost->getAttributes()['type'] ?? $dbPost->type ?? '');
        $payload['video_url'] = $this->postStoredVideoUrl($dbPost);

        if ($dbPost->user) {
            $payload['publisher_username'] = $dbPost->user->username ?? $dbPost->user->full_name ?? $dbPost->user->email ?? '';
            $payload['publisher_email'] = $dbPost->user->email ?? '';
        }

        return $payload;
    }

    /**
     * Minimal Sent-tab payload from a published Post row (metrics zeroed until Graph sync / insights fetch).
     */
    private function sentFacebookPostFromLocalRecord(Post $dbPost, Page $page): array
    {
        $published = Carbon::parse($dbPost->published_at);
        $createdTime = $published->toIso8601String();

        $fullPicture = '';
        $rawImage = $dbPost->getAttributes()['image'] ?? null;
        if (! empty($rawImage)) {
            $img = (string) $rawImage;
            if (str_starts_with($img, 'http://') || str_starts_with($img, 'https://')) {
                $fullPicture = $img;
            } else {
                $fullPicture = fetchFromS3($img);
            }
        }

        $videoUrl = $this->postStoredVideoUrl($dbPost);

        $payload = [
            'id' => $dbPost->post_id,
            'created_time' => $createdTime,
            'message' => (string) ($dbPost->title ?? ''),
            'story' => '',
            // Used by the UI to show Reel/Story badges in the Sent tab.
            // Expected values from scheduled Facebook posts: post/photo, video, reel, story, etc.
            'type' => (string) ($dbPost->type ?? ''),
            'full_picture' => $fullPicture,
            'video_url' => $videoUrl,
            'permalink_url' => $dbPost->facebook_post_url,
            'account_name' => $page->name,
            'account_profile' => $page->profile_image,
            'social_type' => 'facebook',
            'page_db_id' => $page->id,
            'db_post_id' => $dbPost->id,
            'insights' => [
                'post_reactions' => 0,
                'post_impressions' => 0,
                'post_clicks' => 0,
            ],
            'comments' => 0,
            'shares' => 0,
            'from_local_db' => true,
        ];

        if ($dbPost->user) {
            $payload['publisher_username'] = $dbPost->user->username ?? $dbPost->user->full_name ?? $dbPost->user->email ?? '';
            $payload['publisher_email'] = $dbPost->user->email ?? '';
        }

        return $payload;
    }

    private function sentPostsCacheKey(int $userId, int $pageId, string $duration, ?string $since, ?string $until): string
    {
        return implode(':', [
            'schedule_sent_posts',
            'v1',
            'user',
            $userId,
            'page',
            $pageId,
            'duration',
            $duration,
            'since',
            (string) ($since ?? ''),
            'until',
            (string) ($until ?? ''),
        ]);
    }

    private function pinterestSentPostsCacheKey(int $userId, int $boardId, string $duration, ?string $since, ?string $until): string
    {
        return implode(':', [
            'schedule_sent_pinterest_pins',
            'v1',
            'user',
            $userId,
            'board',
            $boardId,
            'duration',
            $duration,
            'since',
            (string) ($since ?? ''),
            'until',
            (string) ($until ?? ''),
        ]);
    }

    private function facebookDurationPostsCacheKey(string $pageId, string $duration, string $since, string $until): string
    {
        return implode(':', [
            'facebook_posts_by_duration',
            'v1',
            'page',
            $pageId,
            'duration',
            $duration,
            'since',
            $since,
            'until',
            $until,
        ]);
    }

    private function fetchPagePostsFromStore(Page $page, string $since, string $until): ?array
    {
        if (empty($page->page_id) || empty($page->access_token)) {
            return null;
        }

        $stored = FacebookPost::query()
            ->where('fb_page_id', $page->page_id)
            ->whereBetween('post_created_date', [$since . ' 00:00:00', $until . ' 23:59:59'])
            ->orderByDesc('post_created_date')
            ->get();

        if ($stored->isNotEmpty()) {
            return $stored->map(function (FacebookPost $row) {
                $post = is_array($row->post_data) ? $row->post_data : [];
                $insights = is_array($row->post_insights) ? $row->post_insights : [];

                if (! isset($post['insights']) || ! is_array($post['insights'])) {
                    $post['insights'] = $insights;
                }

                $post['post_id'] = $post['post_id'] ?? $row->fb_post_id;
                $post['id'] = $post['id'] ?? $row->fb_post_id;
                $post['created_time'] = $post['created_time'] ?? $row->post_created_date;
                $post['permalink_url'] = $post['permalink_url'] ?? $row->permalink_url;
                $post['status_type'] = $post['status_type'] ?? $row->status_type;
                $post['type'] = $post['type'] ?? $row->post_type;
                $post['shares'] = $post['shares'] ?? $row->shares_count;
                $post['comments'] = $post['comments'] ?? $row->comments_count;

                return $post;
            })->values()->all();
        }

        return [];
    }

    private function facebookDurationCachePath(string $pageId, string $duration, string $since, string $until): string
    {
        return implode('/', [
            'facebook-posts-cache',
            'durations',
            'page-' . $pageId,
            $duration . '-' . $since . '-' . $until . '.json',
        ]);
    }

    private function getFacebookDurationCachePosts(string $pageId, string $duration, string $since, string $until): array
    {
        $path = $this->facebookDurationCachePath($pageId, $duration, $since, $until);
        if (! Storage::disk('local')->exists($path)) {
            return [];
        }

        $raw = Storage::disk('local')->get($path);
        $decoded = json_decode($raw, true);
        $posts = $decoded['posts'] ?? [];
        if (! is_array($posts)) {
            return [];
        }

        return $posts;
    }

    private function fetchThreadPostsFromStore(Thread $thread, string $since, string $until, string $duration = 'full_year'): ?array
    {
        if (empty($thread->threads_id) || empty($thread->access_token)) {
            return null;
        }

        $stored = ThreadPost::forCreatedDateRange((int) $thread->id, $since, $until);
        if ($stored->isNotEmpty()) {
            return $stored->map(fn (ThreadPost $row) => $row->toAnalyticsPostArray())->values()->all();
        }

        return [];
    }

    /**
     * Load Pinterest pins for Sent tab from `pinterest_pins` only (sync runs on schedule/cron).
     *
     * @return array<int, array<string, mixed>>|null null when the board has no rows in range
     */
    private function fetchPinterestPinsFromStore(Board $board, string $since, string $until, string $duration = 'full_year'): ?array
    {
        if (empty($board->board_id)) {
            return null;
        }

        $stored = PinterestPin::query()
            ->where('board_id', $board->id)
            ->whereBetween('pin_created_at', [$since.' 00:00:00', $until.' 23:59:59'])
            ->orderByDesc('pin_created_at')
            ->get();

        if ($stored->isNotEmpty()) {
            return $stored->map(fn (PinterestPin $row) => $this->sentTabPostFromPinterestPin($row, $board))->values()->all();
        }

        return [];
    }

    private function parseCreatedTime($value): int
    {
        if (is_string($value)) {
            return strtotime($value) ?: 0;
        }
        if (is_array($value) && isset($value['date'])) {
            return strtotime($value['date']) ?: 0;
        }
        if ($value instanceof \DateTimeInterface) {
            return $value->getTimestamp();
        }

        return 0;
    }

    /**
     * Delete a sent post.
     * - Facebook: delete by external post id + page id (background job).
     * - Threads: delete via `posts` row (`db_post_id`) when scheduled in-app, or by Threads media id
     *   (`threads_post_id` / data-post-id) for feed-only rows; API delete and `thread_posts` purge run in both cases.
     */
    public function deleteSentPost(Request $request)
    {
        $socialType = strtolower((string) $request->input('social_type', 'facebook'));
        $postId = $request->input('id'); // Facebook post id
        $pageId = $request->input('page_id');
        $dbPostId = (int) $request->input('db_post_id');

        if ($socialType === 'threads') {
            $threadsPostId = trim((string) $request->input('threads_post_id', $request->input('post_id', '')));

            if (empty($pageId)) {
                return response()->json(['success' => false, 'message' => 'Account info is required.'], 400);
            }

            if ($dbPostId <= 0 && $threadsPostId === '') {
                return response()->json(['success' => false, 'message' => 'Post id or Threads media id is required.'], 400);
            }

            $user = Auth::user();
            $threadAccount = Thread::where('id', (int) $pageId)->where('user_id', $user->id)->first();
            if (! $threadAccount) {
                return response()->json(['success' => false, 'message' => 'Account not found or access denied.'], 404);
            }

            if ($dbPostId > 0) {
                $dbPost = Post::withoutGlobalScopes()
                    ->where('id', $dbPostId)
                    ->where('account_id', $threadAccount->id)
                    ->where(function ($q) {
                        $q->where('social_type', 'like', '%threads%')
                            ->orWhere('social_type', 'like', '%thread%');
                    })
                    ->first();
                if (! $dbPost) {
                    return response()->json(['success' => false, 'message' => 'Post not found or access denied.'], 404);
                }

                $storedMediaId = trim((string) ($dbPost->post_id ?? ''));
                $externalPostId = $storedMediaId !== '' ? $storedMediaId : $threadsPostId;

                $dbPost->photo()->delete();
                $wasPublished = (int) $dbPost->status === 1;
                PostService::delete($dbPost->id);

                if ($wasPublished && $storedMediaId === '' && $threadsPostId !== '') {
                    DeleteThreadsPostJob::dispatch($threadsPostId, (int) $threadAccount->id);
                }
            } else {
                $externalPostId = $threadsPostId;
                $this->purgeThreadsSentSnapshotPost($threadAccount, $externalPostId);
                DeleteThreadsPostJob::dispatch($externalPostId, (int) $threadAccount->id);
            }

            if ($dbPostId > 0 && $externalPostId !== '') {
                $this->purgeThreadsSentSnapshotPost($threadAccount, $externalPostId);
            }

            return response()->json(['success' => true, 'message' => 'Threads post deleted successfully.']);
        }

        if (empty($postId) || empty($pageId)) {
            return response()->json(['success' => false, 'message' => 'Post id and page id are required.'], 400);
        }

        $user = Auth::user();
        $page = Page::where('id', $pageId)->where('user_id', $user->id)->first();

        if (! $page) {
            return response()->json(['success' => false, 'message' => 'Account not found or access denied.'], 404);
        }

        $this->purgeFacebookSentSnapshotPost($page, (string) $postId);

        $ourPost = Post::withoutGlobalScopes()
            ->where('post_id', (string) $postId)
            ->where('account_id', $page->id)
            ->where('social_type', 'facebook')
            ->first();

        if ($ourPost) {
            if ($ourPost->source === 'schedule' && $this->verifyPostAccountBelongsToUser($ourPost, $user)) {
                $user->decrementFeatureUsage('scheduled_posts_per_account', 1);
            }
            $ourPost->photo()->delete();
            PostService::delete($ourPost->id);
        } else {
            DeleteFacebookPostJob::dispatch((string) $postId, (int) $page->id, null);
        }

        return response()->json(['success' => true, 'message' => 'Post deleted successfully.']);
    }

    /**
     * Remove a Facebook post from stored sent snapshots and clear sent-tab cache.
     */
    private function purgeFacebookSentSnapshotPost(Page $page, string $externalPostId): void
    {
        if ($externalPostId === '') {
            return;
        }

        FacebookPost::query()
            ->where('fb_page_id', $page->page_id)
            ->where('fb_post_id', $externalPostId)
            ->delete();

        $userId = (int) Auth::id();
        $duration = 'full_year';
        $until = now()->format('Y-m-d');
        $since = now()->subYear()->format('Y-m-d');
        Cache::forget($this->facebookDurationPostsCacheKey((string) $page->page_id, $duration, $since, $until));
    }

    /**
     * Remove a Threads post from stored sent snapshots and clear sent-tab cache.
     */
    private function purgeThreadsSentSnapshotPost(Thread $thread, string $externalPostId): void
    {
        if ($externalPostId === '') {
            return;
        }

        ThreadPost::query()
            ->where('thread_id', $thread->id)
            ->where('threads_post_id', $externalPostId)
            ->delete();

        $userId = (int) Auth::id();
        $duration = 'full_year';
        $until = now()->format('Y-m-d');
        $since = now()->subYear()->format('Y-m-d');
        Cache::forget($this->sentPostsCacheKey($userId, (int) $thread->id, $duration, $since, $until));
    }
}
