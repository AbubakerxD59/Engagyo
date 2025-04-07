<?php

namespace App\Http\Controllers\FrontEnd;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

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
}
