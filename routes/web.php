<?php

use App\Models\Page;
use App\Models\Post;
use App\Models\Board;
use App\Models\Tiktok;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GeneralController;
use App\Http\Controllers\ShortLinkRedirectController;
use Illuminate\Support\Facades\DB;

// Short link redirect (public)
Route::get('/s/{code}', ShortLinkRedirectController::class)->name('short.redirect')->where('code', '[a-zA-Z0-9]+');

// General Routes
Route::name("general.")->controller(GeneralController::class)->group(function () {
    Route::get('preview/link', 'previewLink')->name("previewLink");
    Route::post('shorten', 'shortenPublic')->name("shorten");
    Route::post('save-pending-url-tracking', 'savePendingUrlTracking')->name("savePendingUrlTracking");
    Route::get('set-intended-login', 'setIntendedAndShowLogin')->name("setIntendedAndShowLogin");
    Route::get('set-intended-register', 'setIntendedAndShowRegister')->name("setIntendedAndShowRegister");
    Route::get('url-tracking/after-auth', 'urlTrackingAfterAuth')->name("urlTrackingAfterAuth")
        ->middleware('auth:user');
});
Route::get('phpinif', function () {
    echo sys_get_temp_dir();
});
// Test route: publish a comment on a Facebook post
Route::get('test/facebook-comment/{postId}', function ($postId) {
    $post = Post::withoutGlobalScopes()->findOrFail($postId);

    if ($post->social_type !== 'facebook') {
        return response()->json(['error' => 'Post is not a Facebook post'], 400);
    }

    if (empty($post->post_id)) {
        return response()->json(['error' => 'Post has no Facebook post ID (not published yet?)'], 400);
    }

    $page = $post->page;
    $tokenResult = \App\Services\FacebookService::validateToken($page);

    if (!$tokenResult['success']) {
        return response()->json(['error' => $tokenResult['message']], 400);
    }

    $facebookService = new \App\Services\FacebookService();
    $comment = 'This is a test comment from Engagyo at ' . now()->toDateTimeString();
    $result = $facebookService->postComment($post->post_id, $tokenResult['access_token'], $comment);

    return response()->json([
        'post_id' => $post->post_id,
        'comment' => $comment,
        'result' => [
            'success' => $result['success'],
            'message' => $result['message'] ?? null,
            'comment_id' => $result['success'] && isset($result['data'])
                ? ($result['data']->getGraphNode()['id'] ?? null)
                : null,
        ],
    ]);
});

// Admin Routes
require __DIR__ . '/admin.php';
// Payment Gateways routes
require __DIR__ . '/payment_gateways.php';
// Frontend routes
require __DIR__ . '/frontend.php';
// Redirects
require __DIR__ . '/redirect.php';
// User Panel routes
require __DIR__ . '/user.php';
// API Docs
require __DIR__ . '/api-docs.php';
