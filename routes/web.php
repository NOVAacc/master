<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PublicBookingController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'loginPage'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
    Route::get('/register', [AuthController::class, 'registerPage'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
});
Route::middleware('auth')->group(function () {
    Route::post('/appointments/{appointment}/edit', [DashboardController::class, 'editAppointment']);
    Route::post('/appointments/{appointment}/whatsapp', [DashboardController::class, 'retryWhatsApp'])->middleware('throttle:5,1');
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/dashboard', [DashboardController::class, 'show'])->name('dashboard');
    Route::match(['put', 'post'], '/profile', [DashboardController::class, 'profile']);
    Route::post('/services', [DashboardController::class, 'createService']);
    Route::match(['put', 'post'], '/services/{service}', [DashboardController::class, 'updateService']);
    Route::match(['put', 'post'], '/schedule', [DashboardController::class, 'schedule']);
    Route::match(['put', 'post'], '/appointments/{appointment}', [DashboardController::class, 'status']);
});
Route::get('/m/{master:slug}', [PublicBookingController::class, 'show'])->name('public.master');
Route::post('/m/{master:slug}/book', [PublicBookingController::class, 'book'])->middleware('throttle:10,1');
