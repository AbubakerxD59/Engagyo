<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group.
|
*/

// API Version 1
Route::prefix('v1')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Public Routes (No Authentication Required)
    |--------------------------------------------------------------------------
    */

    // Login endpoint - get API keys with email/password
    Route::post('/auth/login', [AuthController::class, 'login']);

    // API Health check
    Route::get('/health', function () {
        return response()->json([
            'success' => true,
            'message' => 'Engagyo API is running',
            'version' => 'v1',
            'timestamp' => now()->toIso8601String(),
        ]);
    });

    /*
    |--------------------------------------------------------------------------
    | Protected Routes (API Key Authentication Required)
    |--------------------------------------------------------------------------
    */
    Route::middleware('auth.apikey')->group(function () {

        /*
        |--------------------------------------------------------------------------
        | Authentication Endpoints
        |--------------------------------------------------------------------------
        */
        Route::prefix('auth')->group(function () {
            // Test authentication
            Route::get('/test', [AuthController::class, 'test']);
        });

        /*
        |--------------------------------------------------------------------------
        | User Endpoints
        |--------------------------------------------------------------------------
        */
        Route::prefix('user')->group(function () {
            // User Profile
            Route::get('/profile', [UserController::class, 'profile']);
            Route::put('/profile', [UserController::class, 'updateProfile']);
            Route::patch('/profile', [UserController::class, 'updateProfile']);

            // User Statistics
            Route::get('/stats', [UserController::class, 'stats']);

            // User Connected Accounts
            Route::get('/accounts', [UserController::class, 'accounts']);
            Route::get('/boards', [UserController::class, 'boards']);
            Route::get('/pages', [UserController::class, 'pages']);
            Route::get('/domains', [UserController::class, 'domains']);
        });

        /*
        |--------------------------------------------------------------------------
        | Post Endpoints
        |--------------------------------------------------------------------------
        */
        Route::prefix('posts')->group(function () {
            // Create and publish a post to Facebook or Pinterest
            Route::post('/', [PostController::class, 'create']);

            // Get post status by ID
            Route::get('/status/{id}', [PostController::class, 'status']);
        });
    });
});
