<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentController;


// Stripe Webhook Route (must be outside middleware)
Route::post('stripe-webhook', [PaymentController::class, 'webhook'])->name('stripe.webhook');
Route::prefix('payment')->name('payment.')->group(function () {
    Route::get("checkout/{packageId}", [PaymentController::class, "checkout"])->name("checkout");
    Route::get("success", [PaymentController::class, "success"])->name("success");
    Route::get("cancel", [PaymentController::class, "cancel"])->name("cancel");
});
