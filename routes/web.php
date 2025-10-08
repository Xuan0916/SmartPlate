<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VerificationController;
use App\Http\Controllers\DashboardController;

Route::get('/', function () {
    return view('welcome');
});


Route::get('/verify-otp/{email?}', [VerificationController::class, 'show'])->name('verify.otp');
Route::post('/verify-otp', [VerificationController::class, 'verify'])->name('verify.otp.submit');
Route::post('/resend-code', [VerificationController::class, 'resend'])->name('resend.code');

Route::get('/set-password', [VerificationController::class, 'showSetPasswordForm'])->name('set.password.form');
Route::post('/set-password', [VerificationController::class, 'submitSetPassword'])->name('set.password.submit');

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
