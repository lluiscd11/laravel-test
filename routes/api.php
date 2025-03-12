<?php

use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

Route::post('/pay/easymoney', [PaymentController::class, 'payEasyMoney']);
Route::post('/pay/superwalletz', [PaymentController::class, 'paySuperWalletz']);
Route::post('/superwalletz/callback', [PaymentController::class, 'superWalletzCallback'])->name('superwalletz.callback');
