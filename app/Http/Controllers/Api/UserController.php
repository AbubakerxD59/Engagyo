<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserController extends BaseController
{
    /**
     * Get the authenticated user's profile.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function profile(Request $request)
    {
        $user = $request->user();

        return $this->successResponse([
            'user' => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'full_name' => $user->full_name,
                'email' => $user->email,
                'phone_number' => $user->phone_number,
                'city' => $user->city,
                'country' => $user->country,
                'address' => $user->address,
                'timezone_id' => $user->timezone_id,
                'created_at' => $user->created_at->toIso8601String(),
            ]
        ]);
    }

    /**
     * Update the authenticated user's profile.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateProfile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'phone_number' => 'sometimes|nullable|string|max:20',
            'city' => 'sometimes|nullable|string|max:255',
            'country' => 'sometimes|nullable|string|max:255',
            'address' => 'sometimes|nullable|string|max:500',
            'timezone_id' => 'sometimes|nullable|exists:timezones,id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        $user = $request->user();
        $user->update($request->only([
            'first_name',
            'last_name',
            'phone_number',
            'city',
            'country',
            'address',
            'timezone_id',
        ]));

        return $this->successResponse([
            'user' => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'full_name' => $user->full_name,
                'email' => $user->email,
                'phone_number' => $user->phone_number,
                'city' => $user->city,
                'country' => $user->country,
                'address' => $user->address,
                'timezone_id' => $user->timezone_id,
            ]
        ], 'Profile updated successfully');
    }

    /**
     * Get the authenticated user's connected accounts (Pinterest, Facebook, etc.).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function accounts(Request $request)
    {
        $user = $request->user();

        // Pinterest accounts
        $pinterestAccounts = $user->pinterest()->get()->map(function ($pinterest) {
            return [
                '_id' => $pinterest->pin_id,
                'username' => $pinterest->username,
                'type' => 'pinterest',
                'profile_image' => !empty($pinterest->profile_image) ? url($pinterest->profile_image) : null,
                'created_at' => $pinterest->created_at->toIso8601String(),
            ];
        });

        // Facebook accounts
        $facebookAccounts = $user->facebook()->get()->map(function ($facebook) {
            return [
                '_id' => $facebook->fb_id,
                'name' => $facebook->name,
                'type' => 'facebook',
                'profile_image' => !empty($facebook->profile_image) ? url($facebook->profile_image) : null,
                'created_at' => $facebook->created_at->toIso8601String(),
            ];
        });

        return $this->successResponse([
            'accounts' => [
                'pinterest' => $pinterestAccounts,
                'facebook' => $facebookAccounts,
            ],
            'total' => $pinterestAccounts->count() + $facebookAccounts->count(),
        ]);
    }

    /**
     * Get the authenticated user's boards.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function boards(Request $request)
    {
        $user = $request->user();
        $boards = $user->boards()->with('pinterest')->get();

        return $this->successResponse([
            'boards' => $boards->map(function ($board) {
                return [
                    '_id' => $board->board_id,
                    'name' => $board->name,
                    'type' => 'pinterest',
                    'pinterest_account' => $board->pinterest ? [
                        '_id' => $board->pinterest->pin_id,
                        'name' => $board->pinterest->username,
                        'profile_image' => !empty($board->pinterest->profile_image) ? url($board->pinterest->profile_image) : null,
                    ] : null,
                    'created_at' => $board->created_at->toIso8601String(),
                ];
            }),
            'total' => $boards->count(),
        ]);
    }

    /**
     * Get the authenticated user's pages.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function pages(Request $request)
    {
        $user = $request->user();
        $pages = $user->pages()->with('facebook')->get();

        return $this->successResponse([
            'pages' => $pages->map(function ($page) {
                return [
                    '_id' => $page->page_id,
                    'name' => $page->name,
                    'type' => 'facebook',
                    'facebook_account' => $page->facebook ? [
                        '_id' => $page->facebook->fb_id,
                        'name' => $page->facebook->username,
                        'profile_image' => !empty($page->facebook->profile_image) ? url($page->facebook->profile_image) : null,
                    ] : null,
                    'created_at' => $page->created_at->toIso8601String(),
                ];
            }),
            'total' => $pages->count(),
        ]);
    }

    /**
     * Get the authenticated user's domains.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function domains(Request $request)
    {
        $user = $request->user();
        $domains = $user->domains()->get();

        return $this->successResponse([
            'domains' => $domains->map(function ($domain) {
                return [
                    '_id' => $domain->id,
                    'name' => $domain->name,
                    'type' => $domain->type,
                    'category' => $domain->category,
                    'created_at' => $domain->created_at->toIso8601String(),
                ];
            }),
            'total' => $domains->count(),
        ]);
    }

    /**
     * Get user statistics/summary.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function stats(Request $request)
    {
        $user = $request->user();

        return $this->successResponse([
            'stats' => [
                'pinterest_accounts' => $user->pinterest()->count(),
                'facebook_accounts' => $user->facebook()->count(),
                'boards' => $user->boards()->count(),
                'pages' => $user->pages()->count(),
                'domains' => $user->domains()->count(),
                'api_keys' => $user->apiKeys()->active()->count(),
            ]
        ]);
    }
}
