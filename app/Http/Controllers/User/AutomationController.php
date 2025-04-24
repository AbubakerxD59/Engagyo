<?php

namespace App\Http\Controllers\User;

use App\Models\Domain;
use App\Models\Post;
use App\Services\FeedService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
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
    public function __construct(Post $post, Domain $domain, Pinterest $pinterest, Board $board)
    {
        $this->post = $post;
        $this->domain = $domain;
        $this->pinterest = $pinterest;
        $this->board = $board;
        $this->feedService = new FeedService();
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
        $account = $data["account"];
        $type = $data["account_type"];
        $domain = $data["domain"];
        $status = $data["status"];
        $search = $data['search_input'];
        $posts = $this->post;
        if (!empty($search)) {
            $posts = $posts->search($search);
        }
        if ($account) {
            if ($type == 'pinterest') {
                $account = $this->board->find($account);
                $account = $account->board_id;
            }
            $posts = $posts->where("account_id", $account);
        }
        if ($domain) {
            $posts = $posts->where("domain_id", $domain);
        }
        if ($status) {
            $posts = $posts->where("status", $status);
        }
        $totalRecordswithFilter = clone $posts;
        $posts = $posts->orderBy('id', 'ASC');
        /*Set limit offset */
        $posts = $posts->offset(intval($data['start']))->limit(intval($data['length']));
        $posts = $posts->get();
        foreach ($posts as $k => $val) {
            $posts[$k]['post'] = view('user.automation.post')->with('post', $val)->render();
            $posts[$k]['account_name'] = "<a href='" . $val->getAccountUrl($val->type) . "'>" . social_icon($val->type) . " " . $val->getAccount($val->type)->name . "</a>";
            $posts[$k]['domain_name'] = $val->getDomain();
            $posts[$k]['publish'] = date("Y-m-d H:i A", strtotime($val->publish_date));
            $posts[$k]['status_view'] = get_post_status($val->status);
            $posts[$k]['action'] = view(view: 'user.automation.action')->with('post', $val)->render();
            $posts[$k] = $val;
        }

        return response()->json([
            'draw' => intval($data['draw']),
            'iTotalRecords' => $iTotalRecords->count(),
            'iTotalDisplayRecords' => $totalRecordswithFilter->count(),
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

    public function feedUrl(Request $request)
    {
        $user = Auth::user();
        $type = $request->type;
        if ($type == 'pinterest') {
            $account = $this->board->find($request->account);
            $account_id = $account ? $account->board_id : '';
        }
        if ($account) {
            $urlDomain = parse_url($request->url, PHP_URL_HOST);
            $search = ["user_id" => $user->id, "account_id" => $account_id, "type" => $type, "name" => $urlDomain];
            $domain = $this->domain->exists($search)->first();
            if (!$domain) {
                $domain = $this->domain->create([
                    "user_id" => $user->id,
                    "account_id" => $account_id,
                    "type" => $type,
                    "name" => $urlDomain,
                ]);
            }
            $posts = $this->feedService->fetch($urlDomain, $domain, $user, $account_id, $type, $request->time);
            if ($posts["success"]) {
                $response = array(
                    "success" => true,
                    "message" => "Posts fetched Succesfully!"
                );
            } else {
                $response = array(
                    "success" => false,
                    "message" => $posts["error"]
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
