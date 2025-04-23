<?php

namespace App\Http\Controllers\User;

use App\Models\Domain;
use App\Models\Post;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class AutomationController extends Controller
{
    private $post;
    private $domain;
    public function __construct(Post $post, Domain $domain)
    {
        $this->post = $post;
        $this->domain = $domain;
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
        dd($request->all());
    }
}
