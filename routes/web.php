<?php

use App\Http\Controllers\MessageController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\KomojuWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/send-message', [MessageController::class, 'showForm'])->name('send-message.form');
Route::post('/send-message', [MessageController::class, 'sendMessage'])->name('send-message.submit');

Route::get('/payment', [PaymentController::class, 'showForm'])->name('payment.form');
Route::post('/payment/checkout', [PaymentController::class, 'checkout'])->name('payment.checkout');
Route::post('/payment/webhook/komoju', [KomojuWebhookController::class, 'handle'])->name('payment.webhook.komoju');


Route::get('/paypay', [PaymentController::class, 'showPayPay'])->name('payment.paypay.view');
Route::post('/paypay', [PaymentController::class, 'paypay'])->name('payment.paypay');

Route::get('/card', [PaymentController::class, 'showCard'])->name('payment.card.view');
Route::post('/card', [PaymentController::class, 'card'])->name('payment.card');
