<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FrontEnd\PinterestController;

Route::name("pinterest.")->controller(PinterestController::class)->group(function () {
    Route::get("pinterest-callback", "pinterestCallback")->name("callback");
    Route::post("add-board", "addBoard")->name("addBoard");
});
