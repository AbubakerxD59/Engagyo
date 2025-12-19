<?php

namespace App\Http\Controllers\FrontEnd;

use App\Http\Controllers\Controller;
use App\Models\Package;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FrontendController extends Controller
{
    public function home()
    {
        return view("frontend.home");
    }

    public function calendarView()
    {
        return view("frontend.calendar-view");
    }

    public function bulkScheduling()
    {
        return view("frontend.bulk-scheduling");
    }

    public function analytics()
    {
        return view("frontend.analytics");
    }

    public function recycling()
    {
        return view("frontend.recycling");
    }

    public function rssFeeds()
    {
        return view("frontend.rss-feeds");
    }

    public function curatePost()
    {
        return view("frontend.curate-post");
    }

    public function linkShortener()
    {
        return view("frontend.linkShortener");
    }

    public function pricing()
    {
        $packages = Package::where('is_active', true)
            ->with(['features' => function ($query) {
                $query->where('is_active', true);
            }])
            ->orderBy('sort_order', 'asc')
            ->orderBy('price', 'asc')
            ->get();

        return view("frontend.pricing", compact('packages'));
    }

    public function blogs()
    {
        return view("frontend.blogs");
    }

    public function terms()
    {
        return view("frontend.terms");
    }

    public function privacy()
    {
        return view("frontend.privacy");
    }
}
