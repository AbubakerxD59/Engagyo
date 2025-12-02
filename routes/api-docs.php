<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Documentation Routes
|--------------------------------------------------------------------------
*/

Route::get('/api/docs', function () {
    $endpoints = getApiEndpoints();
    $baseUrl = url('/api/v1');
    
    return view('api-docs.index', compact('endpoints', 'baseUrl'));
})->name('api.docs');

