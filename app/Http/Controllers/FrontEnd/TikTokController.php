<?php

namespace App\Http\Controllers\FrontEnd;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TikTokController extends Controller
{
    public function tiktokCallback(Request $request)
    {
        return true;
    }
}
