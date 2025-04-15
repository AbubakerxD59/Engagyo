<?php

use App\Http\Controllers\FrontEnd\PinterestController;
use Illuminate\Support\Facades\Route;

Route::name("pinterest.")->controller(PinterestController::class)->group(function () {
    Route::get("pinterest-callback", "pinterestCallback")->name("callback");
});
