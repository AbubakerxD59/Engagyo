<?php

namespace App\Http\Controllers\FrontEnd;

use App\Http\Controllers\Controller;
use App\Services\PinterestService;
use DirkGroenen\Pinterest\Pinterest;
use Illuminate\Http\Request;

class PinterestController extends Controller
{
    private $pinterestService;
    public function __construct()
    {
        $this->pinterestService = new PinterestService();
    }
    public function pinterestCallback(Request $request)
    {
        dd($request->all());
    }
}
