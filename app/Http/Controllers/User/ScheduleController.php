<?php

namespace App\Http\Controllers\User;

use App\Models\Page;
use App\Models\Post;
use App\Models\Board;
use App\Models\Facebook;
use Illuminate\Http\Request;
use App\Jobs\PublishFacebookPost;
use App\Services\FacebookService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class ScheduleController extends Controller
{
    protected $facebookService;
    public function __construct(FacebookService $facebookService)
    {
        $this->facebookService = new FacebookService();
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
        $user = Auth::user();
        // get scheduled active
        $accounts = $user->getScheduledActiveAccounts();
        $content = $request->get("content") ?? null;
        $comment = $request->get("comment") ?? null;
        $file = $request->hasFile("files") ? true : false;
        foreach ($accounts as $account) {
            if ($account->type == "facebook") {
                $facebook = Facebook::where("fb_id", $account->fb_id)->first();
                if ($facebook) {
                    // store in db
                    $post = Post::create([
                        "user_id" => $user->id,
                        "account_id" => $account->id,
                        "type" => "facebook_schedule",
                        "title" => $content,
                        "comment" => $comment,
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
                        $postData = [];
                    } else {
                        $type = "content_only";
                        $postData = ["message" => $content];
                    }
                    $publish_response = PublishFacebookPost::dispatch($post->id, $postData, $access_token, $type);
                }
            }
        }
    }
}
