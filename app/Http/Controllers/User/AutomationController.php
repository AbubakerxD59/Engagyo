<?php

namespace App\Http\Controllers\User;

use App\Models\Domain;
use App\Models\Post;
use App\Services\FeedService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Pinterest;
use Illuminate\Support\Facades\Auth;

class AutomationController extends Controller
{
    private $post;
    private $domain;
    private $pinterest;
    private $feedService;
    public function __construct(Post $post, Domain $domain, Pinterest $pinterest)
    {
        $this->post = $post;
        $this->domain = $domain;
        $this->pinterest = $pinterest;
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
        $search = $data['search']['value'];
        $iTotalRecords = $this->post;
        $posts = $this->post;
        if (!empty($search)) {
            $posts = $posts->search($search);
        }
        $totalRecordswithFilter = clone $posts;
        $posts = $posts->orderBy('id', 'ASC');
        /*Set limit offset */
        $posts = $posts->offset(intval($data['start']))->limit(intval($data['length']));
        $posts = $posts->get();
        foreach ($posts as $k => $val) {
            $posts[$k]['post'] = view('user.automation.post')->with('post', $val)->render();
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

    public function feedUrl(Request $request)
    {
        $user = Auth::user();
        $type = $request->type;
        if ($type == 'pinterest') {
            $account = $this->pinterest->find($request->account);
            $account_id = $account ? $account->pin_id : '';
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
            $posts = $this->feedService->fetch($urlDomain);
            if ($posts["success"]) {
                foreach ($posts["items"] as $item) {
                    $search["url"] = $item['link'];
                    $search["domain_id"] = $domain->id;
                    $post = $this->post->exist($search)->notPublished()->first();
                    if (!$post) {
                        $this->post->crate([
                            "user_id" => $user->id,
                            "account_id" => $account_id,
                            "type" => $type,
                            "title" => $item["title"],
                            "description" => $item["description"],
                            "domain_id" => $domain->id,
                            "url" => $item["url"],
                            "publish_date" => newDateTime($request->time),
                            "status" => 0,
                        ]);
                    }
                }
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
