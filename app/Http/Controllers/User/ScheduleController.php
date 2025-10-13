<?php

namespace App\Http\Controllers\User;

use App\Services\PinterestService;
use Exception;
use App\Models\Page;
use App\Models\Post;
use App\Models\Board;
use App\Models\Facebook;
use App\Models\Pinterest;
use Illuminate\Http\Request;
use App\Jobs\PublishFacebookPost;
use App\Services\FacebookService;
use App\Jobs\PublishPinterestPost;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\Timeslot;
use Illuminate\Support\Facades\Auth;

class ScheduleController extends Controller
{
    protected $facebookService;
    protected $pinterestService;
    public function __construct()
    {
        $this->facebookService = new FacebookService();
        $this->pinterestService = new PinterestService();
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
        if ($action == "publish") {
            if ($link != "false") {
                $response = $this->publishLink($request);
            } else {
                $response = $this->publishPost($request);
            }
        }
        if ($action == "queue") {
            $response = $this->queuePost($request);
        }
        if ($action == "schedule") {
            $response = $this->schedulePost($request);
        }
        return response()->json($response);
    }
    private function publishPost($request)
    {
        try {
            $user = Auth::user();
            // get scheduled active
            $accounts = $user->getScheduledActiveAccounts();
            $content = $request->get("content") ?? null;
            $comment = $request->get("comment") ?? null;
            $file = $request->file("files") ? true : false;
            $image = $request->file("files");
            if ($file) {
                $image = saveImage($request->file("files"));
            } else {
                $image = null;
            }
            foreach ($accounts as $account) {
                if ($account->type == "facebook") {
                    $facebook = Facebook::where("fb_id", $account->fb_id)->first();
                    if ($facebook) {
                        // store in db
                        $post = Post::create([
                            "user_id" => $user->id,
                            "account_id" => $account->page_id,
                            "social_type" => "facebook",
                            "type" => $file ? "photo" : "content_only",
                            "source" => "schedule",
                            "title" => $content,
                            "comment" => $comment,
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
                        $postData = array();
                        if ($file) {
                            $type = "photo";
                            $postData = ["caption" => $content, "url" => $post->image];
                        } else {
                            $type = "content_only";
                            $postData = ["message" => $content];
                        }
                        PublishFacebookPost::dispatch($post->id, $postData, $access_token, $type, $comment);
                    }
                }
                if ($account->type == "pinterest") {
                    $pinterest = Pinterest::where("pin_id", $account->pin_id)->first();
                    if ($pinterest && $file) {
                        // store in db
                        $post = Post::create([
                            "user_id" => $user->id,
                            "account_id" => $account->board_id,
                            "social_type" => "pinterest",
                            "type" => "photo",
                            "source" => "schedule",
                            "title" => $content,
                            "comment" => $comment,
                            "image" => $image,
                            "status" => 0,
                            "publish_date" => date("Y-m-d H:i"),
                        ]);
                        $access_token = $pinterest->access_token;
                        if (!$pinterest->validToken()) {
                            $token = $this->pinterestService->refreshAccessToken($pinterest->refresh_token, $pinterest->id);
                            $access_token = $token["access_token"];
                        }
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
                        PublishPinterestPost::dispatch($post->id, $postData, $access_token);
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
            if ($file) {
                $image = saveImage($request->file("files"));
            } else {
                $image = null;
            }
            foreach ($accounts as $account) {
                if (count($account->timeslots) > 0) {
                    if ($account->type == "facebook") {
                        $facebook = Facebook::where("fb_id", $account->fb_id)->first();
                        if ($facebook) {
                            $nextTime = (new Post)->nextScheduleTime(["user_id" => $user->id, "account_id" => $account->page_id, "type" => "facebook"], $account->timeslots);
                            // store in db
                            Post::create([
                                "user_id" => $user->id,
                                "account_id" => $account->page_id,
                                "social_type" => "facebook",
                                "type" => $file ? "photo" : "content_only",
                                "source" => "schedule",
                                "title" => $content,
                                "comment" => $comment,
                                "image" => $image,
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
                                "type" => "photo",
                                "source" => "schedule",
                                "title" => $content,
                                "comment" => $comment,
                                "image" => $image,
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
            if ($file) {
                $image = saveImage($request->file("files"));
            } else {
                $image = null;
            }
            foreach ($accounts as $account) {
                $scheduleDateTime = date("Y-m-d", strtotime($schedule_date)) . " " . date("H:i", strtotime($schedule_time));
                if ($account->type == "facebook") {
                    $facebook = Facebook::where("fb_id", $account->fb_id)->first();
                    if ($facebook) {
                        // store in db
                        Post::create([
                            "user_id" => $user->id,
                            "account_id" => $account->page_id,
                            "social_type" => "facebook",
                            "type" => $file ? "photo" : "content_only",
                            "source" => "schedule",
                            "title" => $content,
                            "comment" => $comment,
                            "image" => $image,
                            "status" => 0,
                            "publish_date" => $scheduleDateTime,
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
                            "source" => "schedule",
                            "title" => $content,
                            "comment" => $comment,
                            "image" => $image,
                            "status" => 0,
                            "publish_date" => $scheduleDateTime,
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
