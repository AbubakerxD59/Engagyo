<?php

namespace App\Http\Controllers\FrontEnd;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PinterestController extends Controller
{
    public function pinterestCallback(Request $request)
    {
        dd('here', $request->all());
    }
}
