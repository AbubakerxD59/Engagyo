<?php

namespace App\Http\Controllers\FrontEnd;

use App\Http\Controllers\Controller;

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

    public function linkShortener(){
        return view("frontend.linkShortener");
    }

    public function pricing(){
        return view("frontend.pricing");
    }

    public function blogs(){
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
