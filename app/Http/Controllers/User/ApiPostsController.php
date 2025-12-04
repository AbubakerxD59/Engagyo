<?php

namespace App\Http\Controllers\User;

use Exception;
use App\Models\Post;
use App\Models\Page;
use App\Models\Board;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\PostService;
use Illuminate\Support\Facades\Auth;

class ApiPostsController extends Controller
{
    /**
     * Display the API Posts listing page.
     */
    public function index()
    {
        $user = User::with("boards.pinterest", "pages.facebook")->find(Auth::id());
        $accounts = $user->getAccounts();
        return view("user.api-posts.index", compact("accounts"));
    }

    /**
     * Get posts listing for DataTable (only API source posts).
     */
    public function postsListing(Request $request)
    {
        $data = $request->all();

        // Only get posts with source = 'api'
        $posts = Post::with("page.facebook", "board.pinterest", "apiKey")->where('source', 'api');

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
        $posts = $posts->orderByDesc("created_at")->get();
        $posts->append(["post_details", "account_detail", "publish_datetime", "status_view", "action", "api_key_name"]);

        $response = [
            "draw" => intval($data['draw']),
            "iTotalRecords" => Post::where('source', 'api')->count(),
            "iTotalDisplayRecords" => $totalRecordswithFilter->count(),
            "data" => $posts
        ];

        return response()->json($response);
    }

    /**
     * Delete a post.
     */
    public function postDelete(Request $request)
    {
        try {
            $post = Post::where('source', 'api')->findOrFail($request->id);
            $post->photo()->delete();
            PostService::delete($post->id);
            $response = [
                "success" => true,
                "message" => "Post deleted successfully!"
            ];
        } catch (Exception $e) {
            $response = [
                "success" => false,
                "message" => $e->getMessage()
            ];
        }
        return response()->json($response);
    }

    /**
     * Get post edit form.
     */
    public function postEdit(Request $request)
    {
        try {
            $post = Post::with("page.facebook", "board.pinterest")->where('source', 'api')->findOrFail($request->id);
            $view = view("user.api-posts.ajax.edit-post", compact("post"));
            $response = array(
                "success" => true,
                "data" => $view->render(),
                "action" => route('panel.api-posts.post.update', $post->id)
            );
        } catch (Exception $e) {
            $response = array(
                "success" => false,
                "message" => $e->getMessage()
            );
        }
        return response()->json($response);
    }

    /**
     * Update a post.
     */
    public function postUpdate($id, Request $request)
    {
        try {
            $post = Post::where('source', 'api')->findOrFail($id);
            $data = [
                "title" => $request->edit_post_title,
                "url" => $request->edit_post_link,
                "publish_date" => date("Y-m-d", strtotime($request->edit_post_publish_date)) . " " . date("H:i", strtotime($request->edit_post_publish_time)),
            ];
            if ($request->has("edit_post_publish_image") && $request->File("edit_post_publish_image")) {
                $image = saveImage($request->file("edit_post_publish_image"));
                $data['image'] = $image;
            }
            $post->update($data);
            $response = array(
                "success" => true,
                "message" => "Post updated successfully!"
            );
        } catch (Exception $e) {
            $response = array(
                "success" => false,
                "message" => $e->getMessage()
            );
        }
        return response()->json($response);
    }

    /**
     * Publish a post immediately.
     */
    public function postPublishNow(Request $request)
    {
        $response = PostService::publishNow($request->id);
        return response()->json($response);
    }
}
