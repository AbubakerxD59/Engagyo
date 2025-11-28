<?php

namespace App\Http\Controllers\User;

use Exception;
use App\Models\Page;
use App\Models\Post;
use App\Models\User;
use App\Models\Board;
use App\Models\Domain;
use App\Jobs\FetchPost;
use App\Models\Facebook;
use App\Models\Pinterest;
use App\Jobs\RefreshPosts;
use Illuminate\Http\Request;
use App\Services\FeedService;
use App\Services\PostService;
use App\Jobs\PublishFacebookPost;
use App\Services\FacebookService;
use App\Jobs\PublishPinterestPost;
use App\Services\HtmlParseService;
use App\Services\PinterestService;
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
        $user = User::with("boards.pinterest", "pages.facebook")->findOrFail(Auth::id());
        $timeslots = timeslots();
        $accounts = $user->getAccounts();
        return view("user.automation.index", compact("user", "timeslots", "accounts"));
    }

    public function posts(Request $request)
    {
        $user = Auth::user();
        $data = $request->all();
        $iTotalRecords = $this->post;
        $order = $data["order"][0]["dir"];
        $account = $data["account"];
        $type = $data["account_type"];
        $status = $data["status"];
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
            $posts = $posts->where("account_id", $account_id);
        }
        if (count($domain) > 0) {
            $posts = $posts->whereIn("domain_id", $domain);
        }
        if (in_array($status, ['-1', '0', '1'])) {
            $posts = $posts->where("status", $status);
        }
        $totalRecordswithFilter = clone $posts;
        $scheduledTill = $this->post->scheduledTill($search, $type, $data["account"], $domain, $status, $user->id);
        $posts = $posts->orderBy('publish_date', $order);
        /*Set limit offset */
        $posts = $posts->offset(intval($data['start']))->limit(intval($data['length']));
        $posts = $posts->get();
        $posts->append(["post_details", "account_detail", "publish_datetime", "domain_name", "status_view", "action"]);

        return response()->json([
            'draw' => intval($data['draw']),
            'iTotalRecords' => $iTotalRecords->count(),
            'iTotalDisplayRecords' => $totalRecordswithFilter->count(),
            'scheduled_till' => $scheduledTill,
            'last_fetch' => $lastFetch,
            'aaData' => $posts,
        ]);
    }

    public function postDelete(Request $request)
    {
        $id = $request->id;
        if ($id) {
            $user = Auth::user();
            $post = $this->post->with("photo")->find($id);
            if ($post) {
                $postData = $post->toArray();
                $post->photo()->delete();
                if ($post->delete()) {
                    RefreshPosts::dispatch($postData, $user->id);
                }
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
            $user = Auth::user();
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
            $user = Auth::user();
            $type = $request->type;
            $domains = $request->url;
            $times = $request->time;
            if ($type == 'pinterest') {
                $account = $this->board->findOrFail($request->account);
                $account_id = $account->id;
            }
            if ($type == 'facebook') {
                $account = $this->page->findOrFail($request->account);
                $account_id = $account->id;
            }
            foreach ($times as $time) {
                foreach ($domains as $domain) {
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
                        "time" => $time,
                        "exist" => $exist
                    ];
                    // Update last fetch
                    $account->update([
                        "last_fetch" => date("Y-m-d H:i A")
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
                                "message" => $feedUrl['message']
                            );
                        }
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
            $user = User::with("boards.pinterest", "pages.facebook")->find(Auth::id());
            $type = $request->type;
            if ($type == 'pinterest') {
                $account = $this->board->findOrFail($id);
            }
            if ($type == 'facebook') {
                $account = $this->page->find($id);
            }
            $domains = $user->getDomains($account->id);
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
            $user = Auth::user();
            if (!empty($id)) {
                $type = $request->type;
                if ($type == "pinterest") {
                    $post = $this->post->with("board.pinterest")->notPublished()->findOrFail($id);
                    $pinterest = $post->board->pinterest;
                    if (!$pinterest->validToken()) {
                        $token = $this->pinterestService->refreshAccessToken($pinterest->refresh_token, $pinterest->id);
                        $access_token = $token["access_token"];
                    } else {
                        $access_token = $pinterest->access_token;
                    }
                    $postData = PostService::postTypeBody($post);
                    PublishPinterestPost::dispatch($post->id, $postData, $access_token, $post->type);
                    $response = array(
                        "success" => true,
                        "message" => "Your post is being Published!"
                    );
                }
                if ($type == 'facebook') {
                    $post = $this->post->with(relations: "page.facebook")->notPublished()->where("id", $id)->firstOrFail();
                    $page = $post->page;

                    if (!$page->validToken()) {
                        $token = $this->facebookService->refreshAccessToken($page->access_token, $page->id);
                        if ($token["success"]) {
                            $data = $token["data"];
                            $access_token = $data["access_token"];
                            $response = array(
                                "success" => true,
                                "message" => "Your post is being Published!"
                            );
                        } else {
                            $response = array(
                                "success" => false,
                                "message" => $token["message"]
                            );
                            return response()->json($response);
                        }
                    } else {
                        $access_token = $page->access_token;
                    }
                    $postData = PostService::postTypeBody($post);
                    PublishFacebookPost::dispatch($post->id, $postData, $access_token, "link");
                } else {
                    $response = array(
                        "success" => false,
                        "message" => "Something went Wrong!!"
                    );
                }
            } else {
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
            $user = Auth::user();
            if ($type == 'pinterest') {
                $account = $this->board->where("id", $id)->first();
            }
            if ($type == 'facebook') {
                $account = $this->page->where("id", $id)->first();
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
            $user = Auth::user();
            if ($type == 'pinterest') {
                $account = $this->board->with("posts.photo")->where("id", $id)->first();
            }
            if ($type == 'facebook') {
                $account = $this->page->with("posts.photo")->where("id", $id)->first();
            }
            if ($account) {
                $posts = $account->posts()->notPublished();
                if (count($domain) > 0) {
                    $posts = $posts->domainSearch($domain);
                }
                foreach ($posts as $post) {
                    $post->photo()->delete();
                }
                $posts->delete();
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
                    "data" => "Post fixed Successfully!"
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
        $user = User::with("boards.pinterest", "pages.facebook")->find(Auth::id());
        $selected_account = $request->selected_account;
        $selected_type = $request->selected_type;
        $domain = $request->has("domain") ? $request->domain : [];
        if ($selected_account && $selected_type) {
            $data = array(
                "selected_account" => $selected_account,
                "selected_type" => $selected_type,
                "domain" => $domain,
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
}
