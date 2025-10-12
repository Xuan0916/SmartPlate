<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VerificationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\DonationController;
use App\Http\Controllers\NotificationController;

Route::get('/', function () {
    return view('welcome');
});

// OTP Verification Routes
Route::get('/verify-otp/{email?}', [VerificationController::class, 'show'])->name('verify.otp');
Route::post('/verify-otp', [VerificationController::class, 'verify'])->name('verify.otp.submit');
Route::post('/resend-code', [VerificationController::class, 'resend'])->name('resend.code');

// Set Password Routes
Route::get('/set-password', [VerificationController::class, 'showSetPasswordForm'])->name('set.password.form');
Route::post('/set-password', [VerificationController::class, 'submitSetPassword'])->name('set.password.submit');

// Dashboard
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// ✅ Profile Routes
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// ✅ Inventory + Donation + Notification Routes
Route::middleware(['auth'])->group(function () {

    // Inventory CRUD
    Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::post('/inventory', [InventoryController::class, 'store'])->name('inventory.store');
    Route::put('/inventory/{id}', [InventoryController::class, 'update'])->name('inventory.update');
    Route::delete('/inventory/{id}', [InventoryController::class, 'destroy'])->name('inventory.destroy');

    // Convert to Donation
    Route::get('/inventory/{id}/convert', [InventoryController::class, 'convertForm'])->name('inventory.convert.form');
    Route::post('/inventory/{id}/convert', [InventoryController::class, 'convertStore'])->name('inventory.convert.store');

    // 保留旧的测试静态版
    Route::view('/inventory/test', 'managefoodinventory.inventory')->name('inventory.test');

    // ✅ Donation
    Route::get('/donation', [DonationController::class, 'index'])->name('donation.index');
    Route::post('/donation/convert', [DonationController::class, 'convert'])->name('donation.convert');
    Route::delete('/donation/{id}', [DonationController::class, 'destroy'])->name('donation.destroy');
    Route::post('/donations/{id}/redeem', [DonationController::class, 'redeem'])
    ->name('donation.redeem')
    ->middleware('auth');




    // ✅ Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.readAll');
});

// Test
# test inventory

require __DIR__.'/auth.php';
