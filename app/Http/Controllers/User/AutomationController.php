<?php

namespace App\Http\Controllers\User;

use Feed;
use App\Models\Page;
use App\Models\Post;
use App\Models\Board;
use App\Models\Domain;
use App\Jobs\FetchPost;
use App\Models\Facebook;
use App\Models\Pinterest;
use Illuminate\Http\Request;
use App\Services\FeedService;
use App\Services\FacebookService;
use App\Jobs\PublishPinterestPost;
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
        $user = Auth::user();
        return view("user.automation.index", compact("user"));
    }

    public function posts(Request $request)
    {
        $data = $request->all();
        $iTotalRecords = $this->post;
        $order = $data["order"][0]["dir"];
        $account = $data["account"];
        $type = $data["account_type"];
        $status = $data["status"];
        $search = $data['search_input'];
        $domain = isset($data["domain"]) ? $data["domain"] : [];
        $lastFetch = '';
        $posts = $this->post->accountExist();
        if (!empty($search)) {
            $posts = $posts->search($search);
        }
        if ($account) {
            if ($type == 'pinterest') {
                $account = $this->board->find($account);
                $lastFetch = $account->last_fetch;
                $account = $account->board_id;
            }
            if ($type == 'facebook') {
                $account = $this->page->find($account);
                $lastFetch = $account->last_fetch;
                $account = $account->page_id;
            }
            $posts = $posts->where("account_id", $account);
        }
        if (count($domain) > 0) {
            $posts = $posts->whereIn("domain_id", $domain);
        }
        if (in_array($status, ['-1', '0', '1'])) {
            $posts = $posts->where("status", $status);
        }
        $totalRecordswithFilter = clone $posts;
        $scheduledTill = $this->post->scheduledTill($search, $type, $data["account"], $domain, $status);
        $posts = $posts->orderBy('publish_date', $order);
        /*Set limit offset */
        $posts = $posts->offset(intval($data['start']))->limit(intval($data['length']));
        $posts = $posts->get();
        foreach ($posts as $k => $val) {
            $posts[$k]['post'] = view('user.automation.post')->with('post', $val)->render();
            $posts[$k]['account_name'] = "<a href='" . $val->getAccountUrl($val->type, $val->account_id) . "' target='_blank'><img src='" . social_logo($val->type) . "' width='20px' height='20px'></img> " . $val->getAccount($val->type, $val->account_id)->name . "</a>";
            $posts[$k]['domain_name'] = $val->getDomain();
            $posts[$k]['publish'] = date("Y-m-d h:i A", strtotime($val->publish_date));
            $posts[$k]['status_view'] = get_post_status($val->status);
            $posts[$k]['action'] = view(view: 'user.automation.action')->with('post', $val)->render();
            $posts[$k] = $val;
        }

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
            $post = $this->post->find($id);
            if ($post) {
                $post->delete();
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
        $user = Auth::user();
        $type = $request->type;
        $domains = $request->url;
        $time = $request->time;
        $mode = 1;
        if ($type == 'pinterest') {
            $account = $this->board->find($request->account);
            $account_id = $account ? $account->board_id : '';
            $mode = 1;
        }
        if ($type == 'facebook') {
            $account = $this->page->find($request->account);
            $account_id = $account ? $account->page_id : '';
            $mode = 0;
        }
        if ($account) {
            foreach ($domains as $domain) {
                $parsedUrl = parse_url($domain);
                if (isset($parsedUrl['host'])) {
                    $urlDomain = $parsedUrl["host"];
                    $category = isset($parsedUrl["path"]) ? $parsedUrl["path"] : '';
                } else {
                    $urlDomain = $parsedUrl["path"];
                    $category = null;
                }
                $search = ["user_id" => $user->id, "account_id" => $account_id, "type" => $type, "name" => $urlDomain, "category" => $category];
                $domain = $this->domain->exists($search)->first();
                $exist = true;
                if (!$domain) {
                    $exist = false;
                    $domain = $this->domain->create([
                        "user_id" => $user->id,
                        "account_id" => $account_id,
                        "type" => $type,
                        "name" => $urlDomain,
                        "category" => $category
                    ]);
                }
                $data = [
                    "urlDomain" => !empty($category) ? $urlDomain . $category : $urlDomain,
                    "category" => $category,
                    "domain" => $domain->id,
                    "user" => $user->id,
                    "account_id" => $account_id,
                    "type" => $type,
                    "time" => $time,
                    "mode" => $mode,
                    "exist" => $exist
                ];
                $feedService = new FeedService($data);
                $feedService->fetch();
                // FetchPost::dispatch($data);
                $response = array(
                    "success" => true,
                    "message" => "Your posts are being Fetched!"
                );
            }
            // Update last fetch
            $account->update([
                "last_fetch" => date("Y-m-d H:i A")
            ]);
        } else {
            $response = array(
                "success" => false,
                "message" => "Something went Wrong!"
            );
        }
        return response()->json($response);
    }

    public function getDomain(Request $request)
    {
        $id = $request->account_id;
        if (!empty($id)) {
            $user = Auth::user();
            $type = $request->type;
            if ($type == 'pinterest') {
                $account = $this->board->find($id);
                if ($account) {
                    $account_id = $account->board_id;
                } else {
                    $response = [
                        "success" => false,
                        "message" => "Something went Wrong!"
                    ];
                }
            }
            if ($type == 'facebook') {
                $account = $this->page->find($id);
                if ($account) {
                    $account_id = $account->page_id;
                } else {
                    $response = [
                        "success" => false,
                        "message" => "Something went Wrong!"
                    ];
                }
            }
            $domains = $user->getDomains($account_id);
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
        if (!empty($id)) {
            $user = Auth::user();
            $type = $request->type;
            if ($type == "pinterest") {
                $post = $this->post->notPublished()->where("id", $id)->first();
                if ($post) {
                    $board = $this->board->search($post->account_id)->active()->first();
                    if ($board) {
                        $pinterest = $this->pinterest->where("pin_id", operator: $board->pin_id)->first();
                        if ($pinterest) {
                            if (!$pinterest->validToken()) {
                                $token = $this->pinterestService->refreshAccessToken($pinterest->refresh_token);
                                $access_token = $token["access_token"];
                                $pinterest->update([
                                    "access_token" => $token["access_token"],
                                    "expires_in" => $token["expires_in"],
                                    "refresh_token" => $token["refresh_token"],
                                    "refresh_token_expires_in" => $token["refresh_token_expires_in"],
                                ]);
                            } else {
                                $access_token = $pinterest->access_token;
                            }
                            $postData = array(
                                "title" => $post->title,
                                "link" => $post->url,
                                "board_id" => (string) $post->account_id,
                                "media_source" => array(
                                    "source_type" => str_contains($post->image, "http") ? "image_url" : "image_base64",
                                    "url" => $post->image
                                )
                            );
                            PublishPinterestPost::dispatch($post->id, $postData, $access_token);
                            $response = array(
                                "success" => true,
                                "message" => "Your post is being Published!"
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
                } else {
                    $response = array(
                        "success" => false,
                        "message" => "Something went Wrong!"
                    );
                }
            } elseif ($type == 'facebook') {
                $post = $this->post->notPublished()->where("id", $id)->first();
                if ($post) {
                    $page = $this->page->search($post->account_id)->active()->first();
                    if ($page) {
                        $facebook = $this->facebook->where("fb_id", $page->fb_id)->first();
                        if ($facebook) {
                            if (!$page->validToken()) {
                                $token = $this->facebookService->refreshAccessToken($page->access_token);
                                $data = $token["data"];
                                $meta_data = $data["metadata"];
                                $access_token = $data["access_token"];;
                                $page->update([
                                    "access_token" => $access_token,
                                    "expires_in" => $meta_data->getField("data_access_expires_at"),
                                ]);
                            } else {
                                $access_token = $page->access_token;
                            }
                            $postData = [
                                'link' => $post->url,
                                'message' => $post->title,
                            ];
                            $createLink =  $this->facebookService->createLink($access_token, $postData);
                            if ($createLink["success"]) {
                                $createLink = $createLink["data"];
                                $graphNode = $createLink->getGraphNode();
                                $post_id = $graphNode['id'];
                                $post->update([
                                    "post_id" => $post_id,
                                    "status" => 1,
                                    "response" => "Post published Successfully!"
                                ]);

                                $response = array(
                                    "success" => true,
                                    "message" => "Post published Successfully!"
                                );
                            } else {
                                $post->update([
                                    "status" => -1,
                                    "response" => $createLink["message"]
                                ]);

                                $response = array(
                                    "success" => false,
                                    "message" => $createLink["message"]
                                );
                            }
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
                } else {
                    $response = array(
                        "success" => false,
                        "message" => "Something went Wrong!"
                    );
                }
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
                $account = $this->board->userSearch($user->id)->where("id", $id)->first();
            }
            if ($type == 'facebook') {
                $account = $this->page->userSearch($user->id)->where("id", $id)->first();
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
        $domain = $request->domain;
        if (!empty($id)) {
            $user = Auth::user();
            if ($type == 'pinterest') {
                $account = $this->board->userSearch($user->id)->where("id", $id)->first();
            }
            if ($type == 'facebook') {
                $account = $this->page->userSearch($user->id)->where("id", $id)->first();
            }
            if ($account) {
                $account->posts()->domainSearch($domain)->delete();
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
}
