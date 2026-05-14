<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Documentation Routes
|--------------------------------------------------------------------------
*/

Route::get('/api/docs', function () {
    $endpoints = array_values(array_filter(
        getApiEndpoints(),
        static fn (array $e): bool => ! in_array($e['category'] ?? '', ['Posts', 'Videos'], true)
    ));

    $sidebarEndpoints = array_values(array_filter(
        $endpoints,
        static fn (array $e): bool => ! in_array($e['id'] ?? '', ['user-accounts', 'user-boards', 'user-pages'], true)
    ));

    $baseUrl = url('/api/v1');
    $platformPublishingDocs = getApiPlatformPublishingDocs();
    $accountNavLinks = getApiDocAccountNavLinks();

    return view('api-docs.index', compact(
        'endpoints',
        'sidebarEndpoints',
        'baseUrl',
        'platformPublishingDocs',
        'accountNavLinks'
    ));
})->name('api.docs');

