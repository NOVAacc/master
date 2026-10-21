<?php
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AccountSecurityController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\CustomerBookingController;
use App\Http\Controllers\GroupSessionController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MasterImageController;
use App\Http\Controllers\PublicBookingController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'loginPage'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:20,1');
    Route::get('/register', [AuthController::class, 'registerPage'])->name('register');
    Route::post('/auth/send-code', [AuthController::class, 'sendCode'])->middleware('throttle:5,10');
    Route::post('/auth/verify-code', [AuthController::class, 'verifyCode'])->middleware('throttle:10,10');
    Route::get('/forgot-password', [AccountSecurityController::class, 'forgotPage'])->name('password.request');
    Route::post('/forgot-password', [AccountSecurityController::class, 'forgot'])->middleware('throttle:3,10')->name('password.email');
    Route::get('/reset-password/{token}', [AccountSecurityController::class, 'resetPage'])->name('password.reset');
    Route::post('/reset-password', [AccountSecurityController::class, 'reset'])->middleware('throttle:5,10')->name('password.update');
});
Route::middleware('auth')->group(function () {
    Route::get('/appointments/clients', [\App\Http\Controllers\ManualBookingController::class, 'clients'])->middleware('throttle:120,1');
    Route::get('/appointments/slots', [\App\Http\Controllers\ManualBookingController::class, 'slots'])->middleware('throttle:120,1');
    Route::post('/appointments', [\App\Http\Controllers\ManualBookingController::class, 'create'])->middleware('throttle:30,1');
    Route::post('/appointments/{appointment}/edit', [DashboardController::class, 'editAppointment']);
    Route::post('/appointments/{appointment}/whatsapp', [DashboardController::class, 'retryWhatsApp'])->middleware('throttle:5,1');
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/dashboard', [DashboardController::class, 'show'])->name('dashboard');
    Route::post('/group-sessions', [GroupSessionController::class, 'create'])->middleware('throttle:20,1');
    Route::post('/group-sessions/{session}/participants/{participant}/cancel', [GroupSessionController::class, 'cancelParticipant'])->whereNumber('session')->whereNumber('participant')->middleware('throttle:20,1');
    Route::get('/clients/{client}', [ClientController::class, 'show'])->whereNumber('client')->name('clients.show');
    Route::get('/clients/{client}/merge-candidates', [ClientController::class, 'candidates'])->whereNumber('client')->middleware('throttle:120,1');
    Route::post('/clients/{client}/merge', [ClientController::class, 'merge'])->whereNumber('client')->middleware('throttle:20,1');
    Route::delete('/clients/{client}', [ClientController::class, 'destroy'])->whereNumber('client')->middleware('throttle:20,1');
    Route::put('/clients/{client}', [ClientController::class, 'update'])->whereNumber('client')->middleware('throttle:30,1')->name('clients.update');
    Route::post('/security/password', [AccountSecurityController::class, 'password'])->middleware('throttle:5,10');
    Route::post('/security/email', [AccountSecurityController::class, 'email'])->middleware('throttle:3,10');
    Route::post('/security/email/resend', [AccountSecurityController::class, 'resend'])->middleware('throttle:3,10');
    Route::match(['put', 'post'], '/profile', [DashboardController::class, 'profile'])->middleware('throttle:20,1');
    Route::post('/services', [DashboardController::class, 'createService']);
    Route::match(['put', 'post'], '/services/{service}', [DashboardController::class, 'updateService']);
    Route::match(['put', 'post'], '/schedule', [DashboardController::class, 'schedule']);
    Route::match(['put', 'post'], '/appointments/{appointment}', [DashboardController::class, 'status']);
});
Route::get('/security/email/verify/{user}/{hash}', [AccountSecurityController::class, 'verify'])->middleware('signed')->name('security.email.verify');
Route::get('/m/{master:slug}', [PublicBookingController::class, 'show'])->name('public.master');
Route::post('/m/{master:slug}/book', [PublicBookingController::class, 'book'])->middleware('throttle:10,1');
Route::get('/m/{master:slug}/image/{kind}/{version}', MasterImageController::class)->whereIn('kind', ['avatar', 'cover', 'work1', 'work2', 'work3'])->where('version', '[A-Za-z0-9]{40}')->name('master.image');

// Signed, short-lived capability: no account required; mutation still uses CSRF.
Route::get('/booking/manage/{appointment}', [CustomerBookingController::class, 'show'])
    ->whereNumber('appointment')->middleware(['signed', 'throttle:30,1'])->name('booking.manage');
Route::post('/booking/manage/{appointment}', [CustomerBookingController::class, 'update'])
    ->whereNumber('appointment')->middleware(['signed', 'throttle:10,1']);

Route::post('/group-sessions/{session}/register', [GroupSessionController::class, 'register'])->whereNumber('session')->middleware('throttle:10,1');
