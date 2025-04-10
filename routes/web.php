<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\PaymentController;

Route::view('/', 'welcome');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

Route::middleware(['auth', 'throttle:60,1'])->group(function () {
    // Booking routes
    Route::get('/bookings/filter', [BookingController::class, 'filter'])->name('bookings.filter');
    Route::resource('bookings', BookingController::class);
    
    // Payment routes
    Route::get('/payment/prompt', [PaymentController::class, 'prompt'])->name('payment.prompt');
    Route::post('/payment/process', [PaymentController::class, 'process'])
        ->middleware('throttle:5,1')
        ->name('payment.process');
});

require __DIR__.'/auth.php';
