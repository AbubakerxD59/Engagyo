<?php

namespace App\Http\Controllers\User;

use Exception;
use App\Models\Page;
use App\Models\Post;
use App\Models\User;
use App\Models\Board;
use App\Models\Domain;
use App\Models\Tiktok;
use App\Jobs\FetchPost;
use App\Models\Facebook;
use App\Models\Pinterest;
use App\Jobs\RefreshPosts;
use Illuminate\Http\Request;
use App\Services\FeedService;
use App\Services\PostService;
use App\Jobs\PublishTikTokPost;
use App\Services\TikTokService;
use App\Jobs\PublishFacebookPost;
use App\Services\FacebookService;
use App\Jobs\PublishPinterestPost;
use App\Services\HtmlParseService;
use App\Services\PinterestService;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class AutomationController extends Controller
{
    private $post;
    private $domain;
    private $pinterest;
    private $facebook;
    private $board;
    private $page;
    private $pinterestService;
    private $facebookService;
    public function __construct(Post $post, Domain $domain, Pinterest $pinterest, Facebook $facebook, Board $board, Page $page)
    {
        $this->post = $post;
        $this->domain = $domain;
        $this->pinterest = $pinterest;
        $this->facebook = $facebook;
        $this->board = $board;
        $this->page = $page;
        $this->pinterestService = new PinterestService();
        $this->facebookService = new FacebookService();
    }
    public function index()
    {
        $user = User::with("boards.pinterest", "pages.facebook")->findOrFail(Auth::guard('user')->id());
        $timeslots = timeslots();
        $accounts = $user->getAccounts();
        return view("user.automation.index", compact("user", "timeslots", "accounts"));
    }

    public function posts(Request $request)
    {
        $user = Auth::guard('user')->user();
        $data = $request->all();
        $iTotalRecords = $this->post;
        $order = isset($data["order"][0]["dir"]) ? $data["order"][0]["dir"] : 'ASC';
        $account = $data["account"] ?? null;
        $type = $data["account_type"] ?? null;
        $status = $data["status"] ?? null;
        $search = null;
        $domain = isset($data["domain"]) ? $data["domain"] : [];
        $lastFetch = '';
        $posts = $this->post->with("page.facebook", "board.pinterest", "domain")->isRss()->accountExist();
        if ($account) {
            if ($type == 'pinterest') {
                $account = $this->board->findOrFail($account);
                $lastFetch = $account->last_fetched;
                $account_id = $account->id;
            }
            if ($type == 'facebook') {
                $account = $this->page->findOrFail($account);
                $lastFetch = $account->last_fetched;
                $account_id = $account->id;
            }
            if ($type == 'tiktok') {
                $account = Tiktok::findOrFail($account);
                $lastFetch = $account->last_fetched;
                $account_id = $account->id;
            }
            $posts = $posts->where("account_id", $account_id);
        }
        if (count($domain) > 0) {
            $posts = $posts->whereIn("domain_id", $domain);
        }
        if (in_array($status, ['-1', '0', '1'])) {
            $posts = $posts->where("status", $status);
        }
        $totalRecordswithFilter = clone $posts;
        $scheduledTill = $this->post->scheduledTill($search, $type, $data["account"] ?? null, $domain, $status, $user->id);
        $posts = $posts->orderBy('publish_date', $order);
        /*Set limit offset */
        $posts = $posts->offset(intval($data['start'] ?? 0))->limit(intval($data['length'] ?? 9));
        $posts = $posts->get();
        $posts->append(["post_details", "account_detail", "publish_datetime", "domain_name", "status_view", "action", "published_at_formatted"]);

        // Add account_name to each post for the card view
        $posts->transform(function ($post) {
            if ($post->social_type == 'facebook' && $post->page) {
                $post->account_name = $post->page->name;
            } elseif ($post->social_type == 'pinterest' && $post->board) {
                $post->account_name = $post->board->name;
            } elseif ($post->social_type == 'tiktok' && $post->tiktok) {
                $post->account_name = $post->tiktok->name;
            } else {
                $post->account_name = 'Unknown';
            }
            return $post;
        });

        return response()->json([
            'draw' => intval($data['draw'] ?? 1),
            'iTotalRecords' => $iTotalRecords->count(),
            'iTotalDisplayRecords' => $totalRecordswithFilter->count(),
            'scheduled_till' => $scheduledTill,
            'last_fetch' => $lastFetch,
            'data' => $posts,
        ]);
    }

    public function postDelete(Request $request)
    {
        $id = $request->id;
        if ($id) {
            $user = Auth::guard('user')->user();
            $post = $this->post->with("photo")->find($id);
            if ($post) {
                $postData = $post->toArray();
                $post->photo()->delete();
                PostService::delete($post->id);
                RefreshPosts::dispatch($postData, $user->id);
                $response = array(
                    "success" => true,
                    "message" => "Post deleted Successfully!"
                );
            } else {
                $response = array(
                    "success" => false,
                    "message" => "Something went Wrong!"
                );
            }
        } else {
            $response = array(
                "success" => false,
                "message" => "Something went Wrong!"
            );
        }
        return response()->json($response);
    }

    public function postUpdate(Request $request, $id = null)
    {
        $data = $request->validate([
            'post_title' => 'required',
            'post_url' => 'required',
            'post_date' => 'required',
            'post_time' => 'required',
        ]);
        if (!empty($id)) {
            $user = Auth::guard('user')->user();
            $post = $this->post->notPublished()->where("id", $id)->first();
            if ($post) {
                $data = [
                    "title" => $request->post_title,
                    "url" => $request->post_url,
                    "publish_date" => $post->publishDate($request->post_date, $request->post_time),
                ];
                if ($request->has("post_image")) {
                    $data['image'] = saveImage($request->File('post_image'));
                }
                $post->update($data);
                $response = array(
                    "success" => true,
                    "message" => "Post updated Successfully!"
                );
            } else {
                $response = array(
                    "success" => false,
                    "message" => "Something went Wrong!"
                );
            }
        } else {
            $response = array(
                "success" => false,
                "message" => "Something went Wrong!"
            );
        }
        return response()->json($response);
    }

    public function feedUrl(Request $request)
    {
        try {
            $user = Auth::guard('user')->user();
            $feedBody = $request->feedBody;
            $type = $feedBody['type'];
            $account = $feedBody['account'];
            $body = $feedBody['body'];
            if ($type == 'pinterest') {
                $account = $this->board->findOrFail($account);
                $account_id = $account->id;
            }
            if ($type == 'facebook') {
                $account = $this->page->findOrFail($account);
                $account_id = $account->id;
            }
            if ($type == 'tiktok') {
                $account = Tiktok::findOrFail($account);
                $account_id = $account->id;
            }

            // Check if RSS automation is paused for this page or board
            if ($account->rss_paused) {
                return response()->json([
                    "success" => false,
                    "message" => "RSS automation is paused for this account. Please enable RSS automation to fetch posts."
                ]);
            }
            foreach ($body as $item) {
                $times = $item['time'];
                $domain = $item['feed_url'];

                $parsedUrl = parse_url($domain);
                if (isset($parsedUrl['host'])) {
                    $urlDomain = $parsedUrl["host"];
                    if (isset($parsedUrl["path"])) {
                        $category = str_contains($parsedUrl["path"], "rss") || str_contains($parsedUrl["path"], "feed") ? null : $parsedUrl["path"];
                    } else {
                        $category = null;
                    }
                } else {
                    $urlDomain = $parsedUrl["path"];
                    $category = null;
                }
                $search = ["account_id" => $account_id, "type" => $type, "name" => $urlDomain, "category" => $category];
                $domain = $this->domain->exists($search)->first();
                if (!$domain) {
                    $domain = $this->domain->create([
                        "user_id" => $user->id,
                        "account_id" => $account_id,
                        "type" => $type,
                        "name" => $urlDomain,
                        "category" => $category,
                        "time" => $times
                    ]);
                    $domain_id = $domain->id;
                } else {
                    $domain_id = $domain->id;
                    $domain->update([
                        "time" => $times
                    ]);
                }
                $posts = $domain->posts()->get();
                $exist = count($posts) > 0 ? true : false;
                if ($exist) {
                    $link = $urlDomain;
                } else {
                    $link = !empty($category) ? $urlDomain . $category : $urlDomain;
                }

                $data = [
                    "protocol" => $parsedUrl["scheme"],
                    "url" => $link,
                    "category" => $category,
                    "domain_id" => $domain_id,
                    "user_id" => $user->id,
                    "account_id" => $account_id,
                    "type" => "link",
                    "social_type" => $type,
                    "source" => "rss",
                    "time" => $times,
                    "exist" => $exist
                ];
                // Update last fetch
                $account->update([
                    "last_fetch" => date("Y-m-d h:i A")
                ]);
                if ($exist) {
                    FetchPost::dispatch($data);
                    $response = array(
                        "success" => true,
                        "message" => "Your posts are being Fetched!"
                    );
                } else {
                    $feedService = new FeedService($data);
                    $feedUrl = $feedService->fetch();
                    if ($feedUrl['success']) {
                        $response = array(
                            "success" => true,
                            "message" => "Your posts are Fetched!"
                        );
                    } else {
                        $response = array(
                            "success" => false,
                            "message" => $feedUrl['message'] ?? "Failed to fetch RSS feed."
                        );
                    }
                }
            }
        } catch (Exception $e) {
            $response = array(
                "success" => false,
                "message" => $e->getMessage()
            );
        }
        return response()->json($response);
    }

    public function getDomain(Request $request)
    {
        $id = $request->account_id;
        if (!empty($id)) {
            $user = User::with("boards.pinterest", "pages.facebook")->find(Auth::guard('user')->id());
            $type = $request->type;
            if ($type == 'pinterest') {
                $account = $this->board->findOrFail($id);
            }
            if ($type == 'facebook') {
                $account = $this->page->find($id);
            }
            if ($type == 'tiktok') {
                $account = Tiktok::find($id);
            }
            $domains = $user->getDomains($account->id, $type);
            $response = [
                "success" => true,
                "data" => $domains
            ];
        } else {
            $response = [
                "success" => false,
                "message" => "Something went Wrong1!"
            ];
        }
        return response()->json($response);
    }

    public function postPublish(Request $request, $id = null)
    {
        try {
            $user = Auth::guard('user')->user();
            if (!empty($id)) {
                $type = $request->type;
                $post = $this->post->with(["board.pinterest", "page.facebook", "tiktok"])->where("status", "!=", 1)->findOrFail($id);
                $pinterest_active = $post->social_type == "pinterest" ? true : false;
                $dom = new HtmlParseService($pinterest_active);
                $get_info = $dom->get_info($post->url, 1);
                if ($get_info['status']) {
                    $title = !empty($get_info["title"]) ? $get_info["title"] : $post->title;
                    $image = !empty($get_info["image"]) ? $get_info["image"] : $post->image;
                    $post->update([
                        'title' => $title,
                        'image' => $image,
                    ]);
                    $response = [
                        "success" => true,
                        "data" => "Post fixed Successfully!"
                    ];
                } else {
                    $response = [
                        "success" => false,
                        "message" => $get_info['message'] ?? "Image could not be fetched. Try again later!"
                    ];
                    return response()->json($response);
                }
                if ($type == "pinterest") {
                    $board = $post->board;

                    if (!$board) {
                        return response()->json([
                            "success" => false,
                            "message" => "Pinterest board not found."
                        ]);
                    }

                    // Use validateToken for proper error handling
                    $tokenResponse = PinterestService::validateToken($board);

                    if (!$tokenResponse['success']) {
                        return response()->json([
                            "success" => false,
                            "message" => $tokenResponse["message"] ?? "Failed to validate Pinterest access token."
                        ]);
                    }

                    $access_token = $tokenResponse['access_token'];
                    $postData = PostService::postTypeBody($post);
                    PublishPinterestPost::dispatch($post->id, $postData, $access_token, $post->type);
                    $response = array(
                        "success" => true,
                        "message" => "Your post is being Published!"
                    );
                } else if ($type == 'facebook') {
                    $post = $this->post->with(relations: "page.facebook")->notPublished()->where("id", $id)->firstOrFail();
                    $page = $post->page;

                    if (!$page) {
                        return response()->json([
                            "success" => false,
                            "message" => "Facebook page not found."
                        ]);
                    }

                    // Use validateToken for proper error handling
                    $tokenResponse = FacebookService::validateToken($page);

                    if (!$tokenResponse['success']) {
                        return response()->json([
                            "success" => false,
                            "message" => $tokenResponse["message"] ?? "Failed to validate Facebook access token."
                        ]);
                    }

                    $access_token = $tokenResponse['access_token'];
                    $postData = PostService::postTypeBody($post);
                    PublishFacebookPost::dispatch($post->id, $postData, $access_token, "link");
                    $response = array(
                        "success" => true,
                        "message" => "Your post is being Published!"
                    );
                } else if ($type == 'tiktok') {
                    $post = $this->post->with(relations: "tiktok")->notPublished()->where("id", $id)->firstOrFail();
                    $tiktok = $post->tiktok;

                    if (!$tiktok) {
                        return response()->json([
                            "success" => false,
                            "message" => "Tiktok account not found."
                        ]);
                    }

                    // Use validateToken for proper error handling
                    $tokenResponse = TikTokService::validateToken($tiktok);

                    if (!$tokenResponse['success']) {
                        return response()->json([
                            "success" => false,
                            "message" => $tokenResponse["message"] ?? "Failed to validate Tiktok access token."
                        ]);
                    }

                    $access_token = $tokenResponse['access_token'];
                    $postData = PostService::postTypeBody($post);

                    $postData['url'] = saveImageFromUrl($postData['url'], 'uploads') ?? $postData['url'];

                    PublishTikTokPost::dispatch($post->id, $postData, $access_token, "photo");
                    $response = array(
                        "success" => true,
                        "message" => "Your post is being Published!"
                    );
                } else {
                    Log::info("invalid social type", [
                        "type" => $request->type,
                        "postId" => $id,
                    ]);
                    $response = array(
                        "success" => false,
                        "message" => "Something went Wrong!!"
                    );
                }
            } else {
                Log::info("Post not found!", [
                    "type" => $request->type,
                    "postId" => $id,
                ]);
                $response = array(
                    "success" => false,
                    "message" => "Something went Wrong!"
                );
            }
        } catch (Exception $e) {
            $response = array(
                "success" => false,
                "message" => $e->getMessage()
            );
        }
        return response()->json($response);
    }

    public function postShuffle(Request $request)
    {
        $id = $request->account;
        $type = $request->type;
        $shuffle = $request->shuffle;
        if (!empty($id)) {
            $user = Auth::guard('user')->user();
            if ($type == 'pinterest') {
                $account = $this->board->where("id", $id)->first();
            }
            if ($type == 'facebook') {
                $account = $this->page->where("id", $id)->first();
            }
            if ($type == 'tiktok') {
                $account = Tiktok::where("id", $id)->first();
            }
            if ($account) {
                $account->update([
                    "shuffle" => $shuffle
                ]);
                $response = array(
                    "success" => true,
                    "message" => "Shuffle toggle updated Successfully!"
                );
            } else {
                $response = array(
                    "success" => false,
                    "message" => "Something went Wrong!"
                );
            }
        } else {
            $response = array(
                "success" => false,
                "message" => "Something went Wrong!"
            );
        }
        return response()->json($response);
    }

    public function deleteAll(Request $request)
    {
        $id = $request->account;
        $type = $request->type;
        $domain = $request->has("domain") ? $request->domain : [];
        if (!empty($id)) {
            $user = Auth::guard('user')->user();
            if ($type == 'pinterest') {
                $account = $this->board->with("posts.photo")->where("id", $id)->first();
            }
            if ($type == 'facebook') {
                $account = $this->page->with("posts.photo")->where("id", $id)->first();
            }
            if ($type == 'tiktok') {
                $account = Tiktok::find($id);
            }
            if ($account) {
                $posts = $account->posts()->notPublished();
                if (count($domain) > 0) {
                    $posts = $posts->domainSearch($domain);
                }
                foreach ($posts->get() as $post) {
                    $post->photo()->delete();
                    PostService::delete($post->id);
                }
                $response = array(
                    "success" => true,
                    "message" => "Posts deleted Successfully!!"
                );
            } else {
                $response = array(
                    "success" => false,
                    "message" => "Something went Wrong!"
                );
            }
        } else {
            $response = array(
                "success" => false,
                "message" => "Something went Wrong!"
            );
        }
        return response()->json($response);
    }

    public function postFix(Request $request)
    {
        $id = $request->id;
        $post = $this->post->where('id', $id)->notPublished()->first();
        if ($post) {
            $pinterest_active = $post->social_type == "pinterest" ? true : false;
            $dom = new HtmlParseService($pinterest_active);
            $get_info = $dom->get_info($post->url, 1);
            if ($get_info['status']) {
                $title = !empty($get_info["title"]) ? $get_info["title"] : $post->title;
                $image = !empty($get_info["image"]) ? $get_info["image"] : $post->image;
                $post->update([
                    'title' => $title,
                    'image' => $image,
                ]);
                $response = [
                    "success" => true,
                    "message" => "Post fixed Successfully!"
                ];
            } else {
                $response = [
                    "success" => false,
                    "message" => $get_info['message']
                ];
            }
        } else {
            $response = array(
                "success" => false,
                "message" => "Something went Wrong!"
            );
        }
        return response()->json($response);
    }


    public function saveFilters(Request $request)
    {
        $user = User::with("boards.pinterest", "pages.facebook")->find(Auth::guard('user')->id());
        $selected_account = $request->selected_account;
        $selected_type = $request->selected_type;
        if ($selected_account && $selected_type) {
            $data = array(
                "selected_account" => $selected_account,
                "selected_type" => $selected_type,
            );
            $user->update([
                "rss_filters" => $data
            ]);
        }
        $response = [
            "success" => true,
        ];
        return response()->json($response);
    }

    public function deleteDomain(Request $request)
    {
        $domainId = $request->domain_id;

        if (empty($domainId)) {
            return response()->json([
                "success" => false,
                "message" => "No domain selected!"
            ]);
        }

        try {
            $deletedCount = 0;
            $domain = $this->domain->where('id', $domainId)->first();
            if ($domain) {
                // Delete all posts associated with this domain
                $posts = Post::where('domain_id', $domain->id)->get();
                foreach ($posts as $post) {
                    $post->photo()->delete();
                    PostService::delete($post->id);
                }
                // Delete the domain
                $domain->delete();
                $deletedCount++;
            }

            if ($deletedCount > 0) {
                $message = $deletedCount == 1
                    ? "Domain deleted successfully!"
                    : "{$deletedCount} domains deleted successfully!";

                return response()->json([
                    "success" => true,
                    "message" => $message
                ]);
            } else {
                return response()->json([
                    "success" => false,
                    "message" => "No domains found to delete!"
                ]);
            }
        } catch (Exception $e) {
            return response()->json([
                "success" => false,
                "message" => $e->getMessage()
            ]);
        }
    }

    public function saveChanges(Request $request)
    {
        try {
            $user = Auth::guard('user')->user();
            $accountId = $request->account;
            $type = $request->type;
            $deletedPostIds = $request->deleted_post_ids ?? [];
            $timeslotData = $request->timeslot_data ?? [];

            if (empty($accountId) || empty($type)) {
                return response()->json([
                    "success" => false,
                    "message" => "Account information is required!"
                ]);
            }

            // Get account
            if ($type == 'pinterest') {
                $account = $this->board->findOrFail($accountId);
            } elseif ($type == 'facebook') {
                $account = $this->page->findOrFail($accountId);
            } elseif ($type == 'tiktok') {
                $account = Tiktok::findOrFail($accountId);
            } else {
                return response()->json([
                    "success" => false,
                    "message" => "Invalid account type!"
                ]);
            }

            $accountId = $account->id;

            // Delete posts if any
            if (!empty($deletedPostIds) && is_array($deletedPostIds)) {
                $postsToDelete = $this->post->whereIn('id', $deletedPostIds)
                    ->where('account_id', $accountId)
                    ->get();

                // Get the earliest publish date from deleted posts
                $earliestDeletedDate = null;
                foreach ($postsToDelete as $post) {
                    $postDate = strtotime($post->publish_date);
                    if ($earliestDeletedDate === null || $postDate < $earliestDeletedDate) {
                        $earliestDeletedDate = $postDate;
                    }
                    // Delete post photos
                    $post->photo()->delete();
                    PostService::delete($post->id);
                }

                // Rearrange remaining posts
                if ($earliestDeletedDate !== null) {
                    $earliestDate = date('Y-m-d', $earliestDeletedDate);

                    // Get all remaining posts for this account, ordered by publish_date
                    $remainingPosts = $this->post->where('account_id', $accountId)
                        ->where('status', '!=', 1) // Not published
                        ->orderBy('publish_date', 'ASC')
                        ->get();

                    // Rearrange posts starting from the earliest deleted date
                    $currentDate = $earliestDate;
                    foreach ($remainingPosts as $post) {
                        // Get the time from the original publish_date
                        $originalTime = date('H:i:s', strtotime($post->publish_date));
                        $newPublishDate = $currentDate . ' ' . $originalTime;

                        $post->update([
                            'publish_date' => $newPublishDate
                        ]);

                        // Move to next day
                        $currentDate = date('Y-m-d', strtotime($currentDate . ' +1 day'));
                    }
                }
            }

            // Update timeslots if changed and rearrange posts
            if (!empty($timeslotData) && is_array($timeslotData)) {
                // Collect all unique timeslots from all domains
                $allTimeslots = [];
                foreach ($timeslotData as $item) {
                    $feedUrl = $item['feed_url'] ?? null;
                    $times = $item['times'] ?? [];

                    if ($feedUrl && !empty($times)) {
                        $parsedUrl = parse_url($feedUrl);
                        if (isset($parsedUrl['host'])) {
                            $urlDomain = $parsedUrl["host"];
                            if (isset($parsedUrl["path"])) {
                                $category = str_contains($parsedUrl["path"], "rss") || str_contains($parsedUrl["path"], "feed") ? null : $parsedUrl["path"];
                            } else {
                                $category = null;
                            }
                        } else {
                            $urlDomain = $parsedUrl["path"];
                            $category = null;
                        }

                        $search = ["account_id" => $accountId, "type" => $type, "name" => $urlDomain, "category" => $category];
                        $domain = $this->domain->exists($search)->first();

                        if ($domain) {
                            $domain->update([
                                "time" => $times
                            ]);

                            // Collect timeslots
                            foreach ($times as $time) {
                                if (!in_array($time, $allTimeslots)) {
                                    $allTimeslots[] = $time;
                                }
                            }
                        }
                    }
                }

                // Rearrange posts according to updated timeslots
                if (!empty($allTimeslots)) {
                    // Sort timeslots chronologically
                    usort($allTimeslots, function ($a, $b) {
                        $timeA = strtotime($a);
                        $timeB = strtotime($b);
                        return $timeA - $timeB;
                    });

                    // Get all unpublished posts for this account
                    $posts = $this->post->where('account_id', $accountId)
                        ->where('status', '!=', 1) // Not published
                        ->orderBy('publish_date', 'ASC')
                        ->get();

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
                            if (!isset($usedTimeslotsByDate[$currentScheduleDate])) {
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
                                        !in_array($timeslotKey, $usedTimeslotsByDate[$currentScheduleDate])
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

                                // Assign post to this timeslot on current date
                                $publishDateTime = $currentScheduleDate . ' ' . $timeslot24Hour;
                                $post->update([
                                    'publish_date' => $publishDateTime
                                ]);

                                // Mark timeslot as used for this date
                                $usedTimeslotsByDate[$currentScheduleDate][] = $timeslotKey;

                                // Move to next timeslot index
                                $timeslotIndex = ($availableTimeslotIndex + 1) % count($allTimeslots);

                                // If we've used all timeslots for this day, move to next day
                                if (count($usedTimeslotsByDate[$currentScheduleDate]) >= count($allTimeslots)) {
                                    $currentScheduleDate = date('Y-m-d', strtotime($currentScheduleDate . ' +1 day'));
                                    $timeslotIndex = 0;
                                    if (!isset($usedTimeslotsByDate[$currentScheduleDate])) {
                                        $usedTimeslotsByDate[$currentScheduleDate] = [];
                                    }
                                }

                                $assigned = true;
                            } else {
                                // No available timeslot for current date, find next available
                                $attempts = 0;
                                $maxAttempts = count($allTimeslots) * 100; // Safety limit

                                while (!$assigned && $attempts < $maxAttempts) {
                                    // Get current timeslot
                                    $timeslot = $allTimeslots[$timeslotIndex];

                                    // Convert timeslot to 24-hour format
                                    $timeslot24Hour = date('H:i:s', strtotime($timeslot));

                                    // Initialize used timeslots array for current schedule date if not exists
                                    if (!isset($usedTimeslotsByDate[$currentScheduleDate])) {
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
                                            if (!isset($usedTimeslotsByDate[$currentScheduleDate])) {
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
                                        if (!isset($usedTimeslotsByDate[$currentScheduleDate])) {
                                            $usedTimeslotsByDate[$currentScheduleDate] = [];
                                        }
                                        $attempts++;
                                        continue;
                                    }

                                    // Timeslot is available for this date, assign post
                                    $publishDateTime = $currentScheduleDate . ' ' . $timeslot24Hour;
                                    $post->update([
                                        'publish_date' => $publishDateTime
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
                                        if (!isset($usedTimeslotsByDate[$currentScheduleDate])) {
                                            $usedTimeslotsByDate[$currentScheduleDate] = [];
                                        }
                                    }

                                    $assigned = true;
                                }

                                // If we couldn't assign (shouldn't happen with proper logic), fallback
                                if (!$assigned) {
                                    // Fallback: assign to next day with first timeslot
                                    $fallbackDate = date('Y-m-d', strtotime($currentScheduleDate . ' +1 day'));
                                    $fallbackTimeslot = $allTimeslots[0];
                                    $fallbackTime = date('H:i:s', strtotime($fallbackTimeslot));
                                    $publishDateTime = $fallbackDate . ' ' . $fallbackTime;
                                    $post->update([
                                        'publish_date' => $publishDateTime
                                    ]);
                                    $currentScheduleDate = $fallbackDate;
                                    $timeslotIndex = 1;
                                    // Mark as used
                                    if (!isset($usedTimeslotsByDate[$currentScheduleDate])) {
                                        $usedTimeslotsByDate[$currentScheduleDate] = [];
                                    }
                                    $usedTimeslotsByDate[$currentScheduleDate][] = $fallbackTime;
                                }
                            }
                        }
                    }
                }
            }

            return response()->json([
                "success" => true,
                "message" => "Changes saved successfully!"
            ]);
        } catch (Exception $e) {
            return response()->json([
                "success" => false,
                "message" => $e->getMessage()
            ]);
        }
    }
}
