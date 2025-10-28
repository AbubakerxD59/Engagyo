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
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ScheduleController extends Controller
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
        $user = Auth::user();
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
            $user = Auth::user();
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
                    $facebook = Facebook::where("fb_id", $account->fb_id)->first();
                    if ($facebook) {
                        // store in db
                        if ($file) {
                            $type = !empty($image) ?  "photo" : "video";
                        } else {
                            $type = "content_only";
                        }
                        $post = Post::create([
                            "user_id" => $user->id,
                            "account_id" => $account->page_id,
                            "social_type" => "facebook",
                            "type" => $type,
                            "source" => $this->source,
                            "title" => $content,
                            "comment" => $comment,
                            "image" => $image,
                            "video" => $video,
                            "status" => 0,
                            "publish_date" => date("Y-m-d H:i"),
                        ]);
                        $access_token = $account->access_token;
                        if (!$account->validToken()) {
                            $token = $this->facebookService->refreshAccessToken($account->access_token, $account->id);
                            $data = $token["data"];
                            $access_token = $data["access_token"];
                        }
                        $postData = array();
                        if ($file) {
                            if (!empty($image)) {
                                $postData = ["caption" => $content, "url" => $post->image];
                            }
                            if (!empty($video)) {
                                $postData = ["description" => $content, "file_url" => $post->video];
                            }
                        } else {
                            $postData = ["message" => $content];
                        }
                        PublishFacebookPost::dispatch($post->id, $postData, $access_token, $type, $comment);
                    }
                }
                if ($account->type == "pinterest") {
                    $pinterest = Pinterest::where("pin_id", $account->pin_id)->first();
                    if ($pinterest && $file) {
                        // store in db
                        $type = !empty($image) ? "photo" : "video";
                        $post = Post::create([
                            "user_id" => $user->id,
                            "account_id" => $account->board_id,
                            "social_type" => "pinterest",
                            "type" => $type,
                            "source" => $this->source,
                            "title" => $content,
                            "comment" => $comment,
                            "image" => $image,
                            "video" => $video,
                            "status" => 0,
                            "publish_date" => date("Y-m-d H:i"),
                        ]);
                        $access_token = $pinterest->access_token;
                        if (!$pinterest->validToken()) {
                            $token = $this->pinterestService->refreshAccessToken($pinterest->refresh_token, $pinterest->id);
                            $access_token = $token["access_token"];
                        }
                        if ($type == "image") {
                            $encoded_image = file_get_contents($post->image);
                            $encoded_image = base64_encode($encoded_image);
                            $postData = array(
                                "title" => $content,
                                "board_id" => (string) $account->board_id,
                                "media_source" => array(
                                    "source_type" => "image_base64",
                                    "content_type" => "image/jpeg",
                                    "data" => $encoded_image
                                )
                            );
                        }
                        if ($type == "video") {
                            $postData = array(
                                "title" => $post->title,
                                "board_id" => (string) $post->account_id,
                                'media_source'  => [
                                    'source_type' => 'video_id',
                                    'media_id'    => $post->video
                                ]
                            );
                        }
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
            $user = Auth::user();
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
                        $facebook = Facebook::where("fb_id", $account->fb_id)->first();
                        if ($facebook) {
                            $nextTime = (new Post)->nextScheduleTime(["user_id" => $user->id, "account_id" => $account->page_id, "type" => "facebook"], $account->timeslots);
                            // store in db
                            if ($file) {
                                $type = !empty($image) ?  "photo" : "video";
                            } else {
                                $type = "content_only";
                            }
                            Post::create([
                                "user_id" => $user->id,
                                "account_id" => $account->page_id,
                                "social_type" => "facebook",
                                "type" => $type,
                                "source" => $this->source,
                                "title" => $content,
                                "comment" => $comment,
                                "image" => $image,
                                "video" => $video,
                                "status" => 0,
                                "publish_date" => $nextTime,
                            ]);
                        }
                    }
                    if ($account->type == "pinterest") {
                        $pinterest = Pinterest::where("pin_id", $account->pin_id)->first();
                        if ($pinterest && $file) {
                            $nextTime = (new Post)->nextScheduleTime(["user_id" => $user->id, "account_id" => $account->board_id, "type" => "pinterest"], $account->timeslots);
                            // store in db
                            Post::create([
                                "user_id" => $user->id,
                                "account_id" => $account->board_id,
                                "social_type" => "pinterest",
                                "type" => !empty($image) ? "photo" : "video",
                                "source" => $this->source,
                                "title" => $content,
                                "comment" => $comment,
                                "image" => $image,
                                "video" => $video,
                                "status" => 0,
                                "publish_date" => $nextTime,
                            ]);
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
            $user = Auth::user();
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
                    $facebook = Facebook::where("fb_id", $account->fb_id)->first();
                    if ($facebook) {
                        // store in db
                        if ($file) {
                            $type = !empty($image) ?  "photo" : "video";
                        } else {
                            $type = "content_only";
                        }
                        Post::create([
                            "user_id" => $user->id,
                            "account_id" => $account->page_id,
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
                        ]);
                    }
                }
                if ($account->type == "pinterest") {
                    $pinterest = Pinterest::where("pin_id", $account->pin_id)->first();
                    if ($pinterest && $file) {
                        // store in db
                        Post::create([
                            "user_id" => $user->id,
                            "account_id" => $account->board_id,
                            "social_type" => "pinterest",
                            "type" => "photo",
                            "source" => $this->source,
                            "title" => $content,
                            "comment" => $comment,
                            "image" => $image,
                            "video" => $video,
                            "status" => 0,
                            "publish_date" => $scheduleDateTime,
                            "scheduled" => 1
                        ]);
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
            $user = Auth::user();
            // get scheduled active
            $accounts = $user->getScheduledActiveAccounts();
            $content = $request->get("content") ?? null;
            $comment = $request->get("comment") ?? null;
            $url = $request->get("url") ?? null;
            $image = $request->get("image") ?? null;
            if (!empty($url) && !empty($image)) {
                foreach ($accounts as $account) {
                    if ($account->type == "facebook") {
                        $facebook = Facebook::where("fb_id", $account->fb_id)->first();
                        if ($facebook) {
                            // store in db
                            $post = Post::create([
                                "user_id" => $user->id,
                                "account_id" => $account->page_id,
                                "social_type" => "facebook",
                                "type" => "link",
                                "source" => $this->source,
                                "title" => $content,
                                "comment" => $comment,
                                "url" => $url,
                                "image" => $image,
                                "status" => 0,
                                "publish_date" => date("Y-m-d H:i"),
                            ]);

                            $access_token = $account->access_token;
                            if (!$account->validToken()) {
                                $token = $this->facebookService->refreshAccessToken($account->access_token, $account->id);
                                $data = $token["data"];
                                $access_token = $data["access_token"];
                            }
                            $postData = [
                                'link' => $post->url,
                                'message' => $post->title,
                            ];
                            PublishFacebookPost::dispatch($post->id, $postData, $access_token, "link", $comment);
                        }
                    }
                    if ($account->type == "pinterest") {
                        $pinterest = Pinterest::where("pin_id", $account->pin_id)->first();
                        if ($pinterest) {
                            // store in db
                            $post = Post::create([
                                "user_id" => $user->id,
                                "account_id" => $account->board_id,
                                "social_type" => "pinterest",
                                "type" => "link",
                                "source" => $this->source,
                                "title" => $content,
                                "comment" => $comment,
                                "url" => $url,
                                "image" => $image,
                                "status" => 0,
                                "publish_date" => date("Y-m-d H:i"),
                            ]);
                            $access_token = $pinterest->access_token;
                            if (!$pinterest->validToken()) {
                                $token = $this->pinterestService->refreshAccessToken($pinterest->refresh_token, $pinterest->id);
                                $access_token = $token["access_token"];
                            }
                            $postData = array(
                                "title" => $post->title,
                                "link" => $post->url,
                                "board_id" => (string) $post->account_id,
                                "media_source" => array(
                                    "source_type" => "image_url",
                                    "url" => $post->image
                                )
                            );
                            PublishPinterestPost::dispatch($post->id, $postData, $access_token);
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
    private function queueLink($request)
    {
        try {
            $user = Auth::user();
            // get scheduled active
            $accounts = $user->getScheduledActiveAccounts();
            $content = $request->get("content") ?? null;
            $comment = $request->get("comment") ?? null;
            $url = $request->get("url") ?? null;
            $image = $request->get("image") ?? null;
            if (!empty($url) && !empty($image)) {
                foreach ($accounts as $account) {
                    if ($account->type == "facebook") {
                        $facebook = Facebook::where("fb_id", $account->fb_id)->first();
                        if ($facebook) {
                            $nextTime = (new Post)->nextScheduleTime(["user_id" => $user->id, "account_id" => $account->page_id, "type" => "facebook"], $account->timeslots);
                            // store in db
                            $post = Post::create([
                                "user_id" => $user->id,
                                "account_id" => $account->page_id,
                                "social_type" => "facebook",
                                "type" => "link",
                                "source" => $this->source,
                                "title" => $content,
                                "comment" => $comment,
                                "url" => $url,
                                "image" => $image,
                                "status" => 0,
                                "publish_date" => $nextTime,
                            ]);

                            $access_token = $account->access_token;
                            if (!$account->validToken()) {
                                $token = $this->facebookService->refreshAccessToken($account->access_token, $account->id);
                                $data = $token["data"];
                                $access_token = $data["access_token"];
                            }
                            $postData = [
                                'link' => $post->url,
                                'message' => $post->title,
                            ];
                            PublishFacebookPost::dispatch($post->id, $postData, $access_token, "link", $comment);
                        }
                    }
                    if ($account->type == "pinterest") {
                        $pinterest = Pinterest::where("pin_id", $account->pin_id)->first();
                        if ($pinterest) {
                            $nextTime = (new Post)->nextScheduleTime(["user_id" => $user->id, "account_id" => $account->board_id, "type" => "pinterest"], $account->timeslots);
                            // store in db
                            $post = Post::create([
                                "user_id" => $user->id,
                                "account_id" => $account->board_id,
                                "social_type" => "pinterest",
                                "type" => "link",
                                "source" => $this->source,
                                "title" => $content,
                                "comment" => $comment,
                                "url" => $url,
                                "image" => $image,
                                "status" => 0,
                                "publish_date" => $nextTime,
                            ]);
                            $access_token = $pinterest->access_token;
                            if (!$pinterest->validToken()) {
                                $token = $this->pinterestService->refreshAccessToken($pinterest->refresh_token, $pinterest->id);
                                $access_token = $token["access_token"];
                            }
                            $postData = array(
                                "title" => $post->title,
                                "link" => $post->url,
                                "board_id" => (string) $post->account_id,
                                "media_source" => array(
                                    "source_type" => "image_url",
                                    "url" => $post->image
                                )
                            );
                            PublishPinterestPost::dispatch($post->id, $postData, $access_token);
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
            $user = Auth::user();
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
                        $facebook = Facebook::where("fb_id", $account->fb_id)->first();
                        if ($facebook) {
                            // store in db
                            $post = Post::create([
                                "user_id" => $user->id,
                                "account_id" => $account->page_id,
                                "social_type" => "facebook",
                                "type" => "link",
                                "source" => $this->source,
                                "title" => $content,
                                "comment" => $comment,
                                "url" => $url,
                                "image" => $image,
                                "status" => 0,
                                "publish_date" => $scheduleDateTime,
                            ]);

                            $access_token = $account->access_token;
                            if (!$account->validToken()) {
                                $token = $this->facebookService->refreshAccessToken($account->access_token, $account->id);
                                $data = $token["data"];
                                $access_token = $data["access_token"];
                            }
                            $postData = [
                                'link' => $post->url,
                                'message' => $post->title,
                            ];
                            PublishFacebookPost::dispatch($post->id, $postData, $access_token, "link", $comment);
                        }
                    }
                    if ($account->type == "pinterest") {
                        $pinterest = Pinterest::where("pin_id", $account->pin_id)->first();
                        if ($pinterest) {
                            // store in db
                            $post = Post::create([
                                "user_id" => $user->id,
                                "account_id" => $account->board_id,
                                "social_type" => "pinterest",
                                "type" => "link",
                                "source" => $this->source,
                                "title" => $content,
                                "comment" => $comment,
                                "url" => $url,
                                "image" => $image,
                                "status" => 0,
                                "publish_date" => $scheduleDateTime,
                            ]);
                            $access_token = $pinterest->access_token;
                            if (!$pinterest->validToken()) {
                                $token = $this->pinterestService->refreshAccessToken($pinterest->refresh_token, $pinterest->id);
                                $access_token = $token["access_token"];
                            }
                            $postData = array(
                                "title" => $post->title,
                                "link" => $post->url,
                                "board_id" => (string) $post->account_id,
                                "media_source" => array(
                                    "source_type" => "image_url",
                                    "url" => $post->image
                                )
                            );
                            PublishPinterestPost::dispatch($post->id, $postData, $access_token);
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
        $user = Auth::user();
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
                $account = Page::with("timeslots")->where("id", $id)->first();
                $account_id = $account->page_id;
            } else if ($type == "pinterest") {
                $account = Board::with("timeslots")->where("id", $id)->first();
                $account_id = $account->board_id;
            }
            if ($account) {
                // remove previous
                Timeslot::where("user_id", $user->id)->where("account_id", $account_id)->where("account_type", $type)->where("type", "schedule")->delete();
                // create new timeslots
                if (is_array($timeslots)) {
                    foreach ($timeslots as $timeslot) {
                        Timeslot::create([
                            "user_id" => $user->id,
                            "account_id" => $account_id,
                            "account_type" => $type,
                            "timeslot" => $timeslot,
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
}
