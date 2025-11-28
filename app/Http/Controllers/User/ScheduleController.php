<?php

namespace App\Http\Controllers\User;

use Exception;
use App\Models\Page;
use App\Models\Post;
use App\Models\Board;
use App\Models\Facebook;
use App\Models\Timeslot;
use App\Models\Pinterest;
use Illuminate\Http\Request;
use App\Jobs\PublishFacebookPost;
use App\Services\FacebookService;
use App\Jobs\PublishPinterestPost;
use App\Services\PinterestService;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\PostService;
use Illuminate\Support\Facades\Auth;

class  ScheduleController extends Controller
{
    protected $facebookService;
    protected $pinterestService;
    protected $source;
    public function __construct()
    {
        $this->facebookService = new FacebookService();
        $this->pinterestService = new PinterestService();
        $this->source = "schedule";
    }
    public function index()
    {
        $user = User::with("boards.pinterest", "pages.facebook")->find(Auth::id());
        $accounts = $user->getAccounts();
        return view("user.schedule.index", compact("accounts"));
    }
    public function accountStatus(Request $request)
    {
        $type = $request->type;
        $id = $request->id;
        $status = $request->status;
        if ($type == "facebook") {
            $page = Page::find($id);
            if ($page) {
                $page->schedule_status = $status == 1 ? "active" : "inactive";
                $page->save();
                $response = [
                    "success" => true,
                    "message" => "Status changed Successfully!"
                ];
            } else {
                $response = array(
                    "success" => false,
                    "message" => "Something went Wrong!"
                );
            }
        } else if ($type == "pinterest") {
            $board = Board::find($id);
            if ($board) {
                $board->schedule_status = $status == 1 ? "active" : "inactive";
                $board->save();
                $response = [
                    "success" => true,
                    "message" => "Status changed Successfully!"
                ];
            } else {
                $response = array(
                    "success" => false,
                    "message" => "Something went Wrong!"
                );
            }
        }
        return response()->json($response);
    }

    public function processPost(Request $request)
    {
        $action = $request->get("action");
        $link = $request->link;
        if ($link) { //link post
            if ($action == "publish") {
                $response = $this->publishLink($request);
            }
            if ($action == "queue") {
                $response = $this->queueLink($request);
            }
            if ($action == "schedule") {
                $response = $this->scheduleLink($request);
            }
        } else { //no link
            if ($action == "publish") {
                $response = $this->publishPost($request);
            }
            if ($action == "queue") {
                $response = $this->queuePost($request);
            }
            if ($action == "schedule") {
                $response = $this->schedulePost($request);
            }
        }
        return response()->json($response);
    }
    // publish post
    private function publishPost($request)
    {
        try {
            $user = User::with("boards.pinterest", "pages.facebook")->findOrFail(Auth::id());
            // get scheduled active
            $accounts = $user->getScheduledActiveAccounts();
            $content = $request->get("content") ?? null;
            $comment = $request->get("comment") ?? null;
            $file = $request->file("files") ? true : false;
            $image = $video = null;
            if ($file) {
                $is_video = $request->video;
                if ($is_video) {
                    $video = saveToS3($request->file("files"));
                } else {
                    $image = saveImage($request->file("files"));
                }
            }
            foreach ($accounts as $account) {
                if ($account->type == "facebook") {
                    Facebook::where("id", $account->fb_id)->firstOrFail();
                    // store in db
                    if ($file) {
                        $type = !empty($image) ?  "photo" : "video";
                    } else {
                        $type = "content_only";
                    }
                    $data = [
                        "user_id" => $user->id,
                        "account_id" => $account->id,
                        "social_type" => "facebook",
                        "type" => $type,
                        "source" => $this->source,
                        "title" => $content,
                        "comment" => $comment,
                        "image" => $image,
                        "video" => $video,
                        "status" => 0,
                        "publish_date" => date("Y-m-d H:i"),
                    ];
                    $post = PostService::create($data);

                    $access_token = $account->access_token;
                    if (!$account->validToken()) {
                        $token = $this->facebookService->refreshAccessToken($account->access_token, $account->id);
                        if ($token["success"]) {
                            $data = $token["data"];
                            $access_token = $data["access_token"];
                        } else {
                            $response = array(
                                "success" => false,
                                "message" => $token["message"]
                            );
                            return $response;
                        }
                    }
                    $postData = PostService::postTypeBody($post);
                    PublishFacebookPost::dispatch($post->id, $postData, $access_token, $type, $comment);
                }
                if ($account->type == "pinterest") {
                    $pinterest = Pinterest::where("id", $account->pin_id)->firstOrFail();
                    if ($file) {
                        // store in db
                        $type = !empty($image) ? "photo" : "video";
                        $data = [
                            "user_id" => $user->id,
                            "account_id" => $account->id,
                            "social_type" => "pinterest",
                            "type" => $type,
                            "source" => $this->source,
                            "title" => $content,
                            "comment" => $comment,
                            "image" => $image,
                            "video" => $video,
                            "status" => 0,
                            "publish_date" => date("Y-m-d H:i"),
                        ];
                        $post = PostService::create($data);

                        $access_token = $pinterest->access_token;
                        if (!$pinterest->validToken()) {
                            $token = $this->pinterestService->refreshAccessToken($pinterest->refresh_token, $pinterest->id);
                            $access_token = $token["access_token"];
                        }
                        $postData = PostService::postTypeBody($post);
                        PublishPinterestPost::dispatch($post->id, $postData, $access_token, $type);
                    }
                }
            }
            $response = array(
                "success" => true,
                "message" => "Your posts are being Published!"
            );
        } catch (Exception $e) {
            $response = array(
                "success" => false,
                "message" => $e->getMessage()
            );
        }
        sleep(1);
        return $response;
    }
    // queue post
    private function queuePost($request)
    {
        try {
            $user = User::with("boards.pinterest", "pages.facebook")->find(Auth::id());
            // get scheduled active
            $accounts = $user->getScheduledActiveAccounts();
            $content = $request->get("content") ?? null;
            $comment = $request->get("comment") ?? null;
            $file = $request->file("files") ? true : false;
            $image = $request->file("files");
            $image = $video = null;
            if ($file) {
                $is_video = $request->video;
                if ($is_video) {
                    $video = saveToS3($request->file("files"));
                } else {
                    $image = saveImage($request->file("files"));
                }
            }
            foreach ($accounts as $account) {
                if (count($account->timeslots) > 0) {
                    if ($account->type == "facebook") {
                        Facebook::where("id", $account->fb_id)->firstOrFail();
                        $nextTime = (new Post)->nextScheduleTime(["account_id" => $account->id, "social_type" => "facebook", "source" => "schedule"], $account->timeslots);
                        // store in db
                        if ($file) {
                            $type = !empty($image) ?  "photo" : "video";
                        } else {
                            $type = "content_only";
                        }
                        $data = [
                            "user_id" => $user->id,
                            "account_id" => $account->id,
                            "social_type" => "facebook",
                            "type" => $type,
                            "source" => $this->source,
                            "title" => $content,
                            "comment" => $comment,
                            "image" => $image,
                            "video" => $video,
                            "status" => 0,
                            "publish_date" => $nextTime,
                        ];
                        PostService::create($data);
                    }
                    if ($account->type == "pinterest") {
                        Pinterest::where("id", $account->pin_id)->firstOrFail();
                        if ($file) {
                            $nextTime = (new Post)->nextScheduleTime(["account_id" => $account->id, "social_type" => "pinterest", "source" => "schedule"], $account->timeslots);
                            // store in db
                            $type = !empty($image) ? "photo" : "video";
                            $data = [
                                "user_id" => $user->id,
                                "account_id" => $account->id,
                                "social_type" => "pinterest",
                                "type" => $type,
                                "source" => $this->source,
                                "title" => $content,
                                "comment" => $comment,
                                "image" => $image,
                                "video" => $video,
                                "status" => 0,
                                "publish_date" => $nextTime,
                            ];
                            PostService::create($data);
                        }
                    }
                    $response = array(
                        "success" => true,
                        "message" => "Your posts are queued for Later!"
                    );
                } else {
                    $response = array(
                        "success" => false,
                        "message" => "Please select atleast 1 posting hour from Setting!"
                    );
                }
            }
        } catch (Exception $e) {
            $response = array(
                "success" => false,
                "message" => $e->getMessage()
            );
        }
        return $response;
    }
    // schedule post
    private function schedulePost($request)
    {
        try {
            $user = User::with("boards.pinterest", "pages.facebook")->find(Auth::id());
            // get scheduled active
            $accounts = $user->getScheduledActiveAccounts();
            $content = $request->get("content") ?? null;
            $comment = $request->get("comment") ?? null;
            $schedule_date = $request->schedule_date;
            $schedule_time = $request->schedule_time;
            $file = $request->file("files") ? true : false;
            $image = $request->file("files");
            $image = $video = null;
            if ($file) {
                $is_video = $request->video;
                if ($is_video) {
                    $video = saveToS3($request->file("files"));
                } else {
                    $image = saveImage($request->file("files"));
                }
            }
            foreach ($accounts as $account) {
                $scheduleDateTime = date("Y-m-d", strtotime($schedule_date)) . " " . date("H:i", strtotime($schedule_time));
                if ($account->type == "facebook") {
                    Facebook::where("id", $account->fb_id)->firstOrFail();
                    // store in db
                    if ($file) {
                        $type = !empty($image) ?  "photo" : "video";
                    } else {
                        $type = "content_only";
                    }
                    $data = [
                        "user_id" => $user->id,
                        "account_id" => $account->id,
                        "social_type" => "facebook",
                        "type" => $type,
                        "source" => $this->source,
                        "title" => $content,
                        "comment" => $comment,
                        "image" => $image,
                        "video" => $video,
                        "status" => 0,
                        "publish_date" => $scheduleDateTime,
                        "scheduled" => 1
                    ];
                    PostService::create($data);
                }
                if ($account->type == "pinterest") {
                    Pinterest::where("id", $account->pin_id)->firstOrFail();
                    if ($file) {
                        // store in db
                        $type = !empty($image) ? "photo" : "video";
                        $data = [
                            "user_id" => $user->id,
                            "account_id" => $account->id,
                            "social_type" => "pinterest",
                            "type" => $type,
                            "source" => $this->source,
                            "title" => $content,
                            "comment" => $comment,
                            "image" => $image,
                            "video" => $video,
                            "status" => 0,
                            "publish_date" => $scheduleDateTime,
                            "scheduled" => 1
                        ];
                        PostService::create($data);
                    }
                }
                $response = array(
                    "success" => true,
                    "message" => "Your posts are scheduled for Later!"
                );
            }
        } catch (Exception $e) {
            $response = array(
                "success" => false,
                "message" => $e->getMessage()
            );
        }
        return $response;
    }
    // publish link post
    private function publishLink($request)
    {
        try {
            $user = User::with("boards.pinterest", "pages.facebook")->find(Auth::id());
            // get scheduled active
            $accounts = $user->getScheduledActiveAccounts();
            $content = $request->get("content") ?? null;
            $comment = $request->get("comment") ?? null;
            $url = $request->get("url") ?? null;
            $image = $request->get("image") ?? null;
            if (!empty($url) && !empty($image)) {
                foreach ($accounts as $account) {
                    if ($account->type == "facebook") {
                        Facebook::where("id", $account->fb_id)->firstOrFail();
                        // store in db
                        $data = [
                            "user_id" => $user->id,
                            "account_id" => $account->id,
                            "social_type" => "facebook",
                            "type" => "link",
                            "source" => $this->source,
                            "title" => $content,
                            "comment" => $comment,
                            "url" => $url,
                            "image" => $image,
                            "status" => 0,
                            "publish_date" => date("Y-m-d H:i"),
                        ];
                        $post = PostService::create($data);

                        $access_token = $account->access_token;
                        if (!$account->validToken()) {
                            $token = $this->facebookService->refreshAccessToken($account->access_token, $account->id);
                            if ($token["success"]) {
                                $data = $token["data"];
                                $access_token = $data["access_token"];
                            } else {
                                $response = array(
                                    "success" => false,
                                    "message" => $token["message"]
                                );
                                return $response;
                            }
                        }
                        $postData = PostService::postTypeBody($post);
                        PublishFacebookPost::dispatch($post->id, $postData, $access_token, "link", $comment);
                    }
                    if ($account->type == "pinterest") {
                        $pinterest = Pinterest::where("id", $account->pin_id)->firstOrFail();
                        // store in db
                        $data = [
                            "user_id" => $user->id,
                            "account_id" => $account->id,
                            "social_type" => "pinterest",
                            "type" => "link",
                            "source" => $this->source,
                            "title" => $content,
                            "comment" => $comment,
                            "url" => $url,
                            "image" => $image,
                            "status" => 0,
                            "publish_date" => date("Y-m-d H:i"),
                        ];
                        $post = PostService::create($data);

                        $access_token = $pinterest->access_token;
                        if (!$pinterest->validToken()) {
                            $token = $this->pinterestService->refreshAccessToken($pinterest->refresh_token, $pinterest->id);
                            $access_token = $token["access_token"];
                        }
                        $postData = PostService::postTypeBody($post);
                        PublishPinterestPost::dispatch($post->id, $postData, $access_token, "link");
                    }
                }
                $response = array(
                    "success" => true,
                    "message" => "Your posts are being Published!"
                );
            } else {
                $response = array(
                    "success" => false,
                    "message" => "Invalid link provided!"
                );
            }
        } catch (Exception $e) {
            $response = array(
                "success" => false,
                "message" => $e->getMessage()
            );
        }
        sleep(1);
        return $response;
    }
    // publish link post
    private function queueLink($request)
    {
        try {
            $user = User::with("boards.pinterest", "pages.facebook")->find(Auth::id());
            // get scheduled active
            $accounts = $user->getScheduledActiveAccounts();
            $content = $request->get("content") ?? null;
            $comment = $request->get("comment") ?? null;
            $url = $request->get("url") ?? null;
            $image = $request->get("image") ?? null;
            if (!empty($url) && !empty($image)) {
                foreach ($accounts as $account) {
                    if ($account->type == "facebook") {
                        Facebook::where("id", $account->fb_id)->firstOrFail();
                        $nextTime = (new Post)->nextScheduleTime(["account_id" => $account->id, "social_type" => "facebook", "source" => "schedule"], $account->timeslots);
                        // store in db
                        $data = [
                            "user_id" => $user->id,
                            "account_id" => $account->id,
                            "social_type" => "facebook",
                            "type" => "link",
                            "source" => $this->source,
                            "title" => $content,
                            "comment" => $comment,
                            "url" => $url,
                            "image" => $image,
                            "status" => 0,
                            "publish_date" => $nextTime
                        ];
                        $post = PostService::create($data);

                        $access_token = $account->access_token;
                        if (!$account->validToken()) {
                            $token = $this->facebookService->refreshAccessToken($account->access_token, $account->id);
                            if ($token["success"]) {
                                $data = $token["data"];
                                $access_token = $data["access_token"];
                            } else {
                                $response = array(
                                    "success" => false,
                                    "message" => $token["message"]
                                );
                                return $response;
                            }
                        }
                        $postData = PostService::postTypeBody($post);
                        PublishFacebookPost::dispatch($post->id, $postData, $access_token, "link", $comment);
                    }
                    if ($account->type == "pinterest") {
                        $pinterest = Pinterest::where("id", $account->pin_id)->firstOrFail();
                        if ($pinterest) {
                            $nextTime = (new Post)->nextScheduleTime(["account_id" => $account->id, "social_type" => "pinterest", "source" => "schedule"], $account->timeslots);
                            // store in db
                            $data = [
                                "user_id" => $user->id,
                                "account_id" => $account->id,
                                "social_type" => "pinterest",
                                "type" => "link",
                                "source" => $this->source,
                                "title" => $content,
                                "comment" => $comment,
                                "url" => $url,
                                "image" => $image,
                                "status" => 0,
                                "publish_date" => $nextTime,
                            ];
                            $post = PostService::create($data);

                            $access_token = $pinterest->access_token;
                            if (!$pinterest->validToken()) {
                                $token = $this->pinterestService->refreshAccessToken($pinterest->refresh_token, $pinterest->id);
                                $access_token = $token["access_token"];
                            }
                            $postData = PostService::postTypeBody($post);
                            PublishPinterestPost::dispatch($post->id, $postData, $access_token, "link");
                        }
                    }
                }
                $response = array(
                    "success" => true,
                    "message" => "Your posts are being Published!"
                );
            } else {
                $response = array(
                    "success" => false,
                    "message" => "Invalid link provided!"
                );
            }
        } catch (Exception $e) {
            $response = array(
                "success" => false,
                "message" => $e->getMessage()
            );
        }
        sleep(1);
        return $response;
    }
    // publish link post
    private function scheduleLink($request)
    {
        try {
            $user = User::with("boards.pinterest", "pages.facebook")->find(Auth::id());
            // get scheduled active
            $accounts = $user->getScheduledActiveAccounts();
            $content = $request->get("content") ?? null;
            $comment = $request->get("comment") ?? null;
            $schedule_date = $request->schedule_date;
            $schedule_time = $request->schedule_time;
            $url = $request->get("url") ?? null;
            $image = $request->get("image") ?? null;
            if (!empty($url) && !empty($image)) {
                foreach ($accounts as $account) {
                    $scheduleDateTime = date("Y-m-d", strtotime($schedule_date)) . " " . date("H:i", strtotime($schedule_time));
                    if ($account->type == "facebook") {
                        Facebook::where("id", $account->fb_id)->firstOrFail();
                        // store in db
                        $data = [
                            "user_id" => $user->id,
                            "account_id" => $account->id,
                            "social_type" => "facebook",
                            "type" => "link",
                            "source" => $this->source,
                            "title" => $content,
                            "comment" => $comment,
                            "url" => $url,
                            "image" => $image,
                            "status" => 0,
                            "publish_date" => $scheduleDateTime,
                        ];
                        $post = PostService::create($data);

                        $access_token = $account->access_token;
                        if (!$account->validToken()) {
                            $token = $this->facebookService->refreshAccessToken($account->access_token, $account->id);
                            if ($token["success"]) {
                                $data = $token["data"];
                                $access_token = $data["access_token"];
                            } else {
                                $response = array(
                                    "success" => false,
                                    "message" => $token["message"]
                                );
                                return $response;
                            }
                        }
                        $postData = PostService::postTypeBody($post);
                        PublishFacebookPost::dispatch($post->id, $postData, $access_token, "link", $comment);
                    }
                    if ($account->type == "pinterest") {
                        $pinterest = Pinterest::where("id", $account->pin_id)->firstOrFail();
                        if ($pinterest) {
                            // store in db
                            $data = [
                                "user_id" => $user->id,
                                "account_id" => $account->id,
                                "social_type" => "pinterest",
                                "type" => "link",
                                "source" => $this->source,
                                "title" => $content,
                                "comment" => $comment,
                                "url" => $url,
                                "image" => $image,
                                "status" => 0,
                                "publish_date" => $scheduleDateTime,
                            ];
                            $post = PostService::create($data);

                            $access_token = $pinterest->access_token;
                            if (!$pinterest->validToken()) {
                                $token = $this->pinterestService->refreshAccessToken($pinterest->refresh_token, $pinterest->id);
                                $access_token = $token["access_token"];
                            }
                            $postData = PostService::postTypeBody($post);
                            PublishPinterestPost::dispatch($post->id, $postData, $access_token, "link");
                        }
                    }
                }
                $response = array(
                    "success" => true,
                    "message" => "Your posts are being Published!"
                );
            } else {
                $response = array(
                    "success" => false,
                    "message" => "Invalid link provided!"
                );
            }
        } catch (Exception $e) {
            $response = array(
                "success" => false,
                "message" => $e->getMessage()
            );
        }
        sleep(1);
        return $response;
    }
    public function getSetting(Request $request)
    {
        $user = User::with("boards.pinterest", "pages.facebook")->find(Auth::id());
        $accounts = $user->getAccounts();
        $view = view("user.schedule.ajax.settings", compact("accounts"));
        $response = [
            "success" => true,
            "data" => $view->render()
        ];
        return response()->json($response);
    }
    public function timeslotSetting(Request $request)
    {
        $user = Auth::user();
        try {
            $type = $request->type;
            $id = $request->id;
            $timeslots = $request->timeslots;
            $account = null;
            if ($type == "facebook") {
                $account = Page::with("timeslots")->where("id", $id)->firstOrFail();
                $account_id = $account->id;
            } else if ($type == "pinterest") {
                $account = Board::with("timeslots")->where("id", $id)->firstOrFail();
                $account_id = $account->id;
            }
            if ($account) {
                // remove previous
                Timeslot::where("account_id", $account_id)->where("account_type", $type)->where("type", "schedule")->delete();
                // create new timeslots
                if (is_array($timeslots)) {
                    foreach ($timeslots as $timeslot) {
                        Timeslot::create([
                            "user_id" => $user->id,
                            "account_id" => $account_id,
                            "account_type" => $type,
                            "timeslot" => date("H:i", strtotime($timeslot)),
                            "type" => "schedule",
                        ]);
                    }
                }
                $response = array(
                    "success" => true,
                    "message" => "Timeslot updated Successfully!"
                );
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

    public function postsListing(Request $request)
    {
        $data = $request->all();
        $posts = Post::with("page.facebook", "board.pinterest")->isScheduled();
        // filters
        if (!empty($request->account_id)) {
            $posts = $posts->whereIn("account_id", $request->account_id);
        }
        if (!empty($request->type)) {
            $posts = $posts->whereIn("social_type", $request->type);
        }
        if (!empty($request->post_type)) {
            $posts = $posts->whereIn("type", $request->post_type);
        }
        if (!empty($request->status)) {
            $posts = $posts->whereIn("status", $request->status);
        }
        $totalRecordswithFilter = clone $posts;
        $posts = $posts->offset(intval($data['start']))->limit(intval($data['length']));
        $posts = $posts->orderBy("publish_date")->get();
        $posts->append(["post_details", "account_detail", "publish_datetime", "status_view", "action"]);
        $response = [
            "draw" => intval($data['draw']),
            "iTotalRecords" => Post::count(),
            "iTotalDisplayRecords" => $totalRecordswithFilter->count(),
            "data" => $posts
        ];
        return response()->json($response);
    }

    public function postDelete(Request $request)
    {
        try {
            $post = Post::findOrFail($request->id);
            $post->photo()->delete();
            PostService::delete($post->id);
            $response = [
                "success" => true,
                "message" => "Post delete Successfully!"
            ];
        } catch (Exception $e) {
            $response = [
                "success" => false,
                "message" => $e->getMessage()
            ];
        }
        return response()->json($response);
    }

    public function postEdit(Request $request)
    {
        try {
            $post = Post::with("page.facebook", "board.pinterest")->findOrFail($request->id);
            $view = view("user.schedule.ajax.edit-post", compact("post"));
            $response = array(
                "success" => true,
                "data" => $view->render(),
                "action" => route('panel.schedule.post.update', $post->id)
            );
        } catch (Exception $e) {
            $response = array(
                "success" => false,
                "message" => $e->getMessage()
            );
        }
        return response()->json($response);
    }
    public function postUpdate($id, Request $request)
    {
        try {
            $post = Post::findOrFail($id);
            $data = [
                "title" => $request->edit_post_title,
                "url" => $request->edit_post_link,
                "comment" => $request->edit_post_comment,
                "publish_date" => date("Y-m-d", strtotime($request->edit_post_publish_date)) . " " . date("H:i", strtotime($request->edit_post_publish_time)),
            ];
            if ($request->has("edit_post_publish_image") && $request->File("edit_post_publish_image")) {
                $image = saveImage($request->file("edit_post_publish_image"));
                $data['image'] = $image;
            }
            $post->update($data);
            $response = array(
                "success" => true,
                "message" => "Post updated Successfully!"
            );
        } catch (Exception $e) {
            $response = array(
                "success" => false,
                "message" => $e->getMessage()
            );
        }
        return response()->json($response);
    }

    public function postPublishNow(Request $request)
    {
        $response = PostService::publishNow($request->id);
        return response()->json($response);
    }
}
