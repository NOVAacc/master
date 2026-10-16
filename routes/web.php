<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PublicBookingController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route(auth()->check() ? 'dashboard' : 'login'));
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'loginPage'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
    Route::get('/register', [AuthController::class, 'registerPage'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
});
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/dashboard', [DashboardController::class, 'show'])->name('dashboard');
    Route::put('/profile', [DashboardController::class, 'profile']);
    Route::post('/services', [DashboardController::class, 'createService']);
    Route::put('/services/{service}', [DashboardController::class, 'updateService']);
    Route::put('/schedule', [DashboardController::class, 'schedule']);
    Route::put('/appointments/{appointment}', [DashboardController::class, 'status']);
});
Route::get('/m/{master:slug}', [PublicBookingController::class, 'show'])->name('public.master');
Route::post('/m/{master:slug}/book', [PublicBookingController::class, 'book'])->middleware('throttle:10,1');
