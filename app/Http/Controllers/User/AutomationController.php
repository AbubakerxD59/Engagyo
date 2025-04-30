<?php

namespace App\Http\Controllers\User;

use App\Jobs\FetchPost;
use App\Models\Domain;
use App\Models\Post;
use App\Services\FeedService;
use App\Services\PinterestService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Jobs\PublishPinterestPost;
use App\Models\Board;
use App\Models\Pinterest;
use Illuminate\Support\Facades\Auth;

class AutomationController extends Controller
{
    private $post;
    private $domain;
    private $pinterest;
    private $board;
    private $feedService;
    private $pinterestService;
    private $fetchPostJob;
    public function __construct(Post $post, Domain $domain, Pinterest $pinterest, Board $board)
    {
        $this->post = $post;
        $this->domain = $domain;
        $this->pinterest = $pinterest;
        $this->board = $board;
        $this->feedService = new FeedService();
        $this->pinterestService = new PinterestService();
        // $this->fetchPostJob = new FetchPost();
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
        $posts = $this->post;
        if (!empty($search)) {
            $posts = $posts->search($search);
        }
        if ($account) {
            if ($type == 'pinterest') {
                $account = $this->board->find($account);
                $lastFetch = $account->last_fetch;
                $account = $account->board_id;
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
        if ($account) {
            foreach ($domains as $domain) {
                $parsedUrl = parse_url($domain);
                if (isset($parsedUrl['host'])) {
                    $urlDomain = $parsedUrl["host"];
                    $category = $parsedUrl["path"];
                } else {
                    $urlDomain = $parsedUrl["path"];
                    $category = null;
                }
                $search = ["user_id" => $user->id, "account_id" => $account_id, "type" => $type, "name" => $urlDomain, "category" => $category];
                $domain = $this->domain->exists($search)->first();
                if (!$domain) {
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
                ];
                FetchPost::dispatch($data);
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
                        $pinterest = $this->pinterest->where("pin_id", $board->pin_id)->first();
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
                            // $publish = $this->pinterestService->create($access_token, $postData);
                            // if (isset($publish["id"])) {
                            //     $post->update([
                            //         "post_id" => $publish["id"],
                            //         "status" => 1
                            //     ]);

                            // } else {
                            //     $post->update([
                            //         "status" => -1
                            //     ]);
                            //     $response = array(
                            //         "success" => false,
                            //         "message" => "Failed to publish Post!"
                            //     );
                            // }
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
