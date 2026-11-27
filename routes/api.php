<?php

use App\Http\Controllers\PaymentWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/webhooks/kkiapay', PaymentWebhookController::class)
    ->middleware('throttle:60,1')
    ->name('payments.kkiapay.webhook');
