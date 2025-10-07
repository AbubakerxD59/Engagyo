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
        if ($action == "publish") {
            $response = $this->publishPost($request);
        }
        if ($action == "schedule") {
            $response = '';
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
                            "type" => "facebook_schedule",
                            "title" => $content,
                            "comment" => $comment,
                            "image" => $image,
                            "publish_date" => date("Y-m-d H:i:s"),
                            "status" => 0,
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
                            Log::info(json_encode($postData));
                        } else {
                            $type = "content_only";
                            $postData = ["message" => $content];
                        }
                        PublishFacebookPost::dispatch($post->id, $postData, $access_token, $type, $comment);
                    }
                }
                if ($account->type == "pinterest") {
                    $pinterest = Pinterest::where("pin_id", $account->pin_id)->first();
                    if ($pinterest) {
                        $postData = array(
                            'title' => $content,
                            'description' => $content,
                            'board_id' => $account->board_id,
                            'link' => "",
                            'image' => $image,
                            'content_type' => 'image_path',
                            'access_token' => $pinterest->access_token,
                        );
                        $access_token = $pinterest->access_token;
                        if (!$pinterest->validToken()) {
                            $token = $this->pinterestService->refreshAccessToken($pinterest->refresh_token, $pinterest->id);
                            $access_token = $token["access_token"];
                        }
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
        return response()->json($response);
    }
}
