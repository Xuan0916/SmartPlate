<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VerificationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\DonationController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\BrowseController;
use App\Http\Controllers\MealPlanController;

Route::get('/', function () {
    return view('welcome');
});

// ✅ OTP Verification Routes
Route::get('/verify-otp/{email?}', [VerificationController::class, 'show'])->name('verify.otp');
Route::post('/verify-otp', [VerificationController::class, 'verify'])->name('verify.otp.submit');
Route::post('/resend-code', [VerificationController::class, 'resend'])->name('resend.code');

// ✅ Set Password Routes
Route::get('/set-password', [VerificationController::class, 'showSetPasswordForm'])->name('set.password.form');
Route::post('/set-password', [VerificationController::class, 'submitSetPassword'])->name('set.password.submit');

// ✅ Dashboard
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

    // ✅ Inventory Routes
    Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::post('/inventory', [InventoryController::class, 'store'])->name('inventory.store');
    Route::get('/inventory/{id}/edit', [InventoryController::class, 'edit'])->name('inventory.edit');
    Route::put('/inventory/{id}', [InventoryController::class, 'update'])->name('inventory.update');
    Route::delete('/inventory/{id}', [InventoryController::class, 'destroy'])->name('inventory.destroy');

    // ✅ Convert to Donation
    Route::get('/inventory/{id}/convert', [InventoryController::class, 'convertForm'])->name('inventory.convert.form');
    Route::post('/inventory/{id}/convert', [InventoryController::class, 'convertStore'])->name('inventory.convert.store');
    Route::put('/inventory/{id}/mark-used', [InventoryController::class, 'markUsed'])->name('inventory.markUsed');
    Route::put('/inventory/{id}/plan-meal', [InventoryController::class, 'planMeal'])->name('inventory.planMeal');

    // ✅ Test static inventory page
    Route::view('/inventory/test', 'managefoodinventory.inventory')->name('inventory.test');

    // ✅ Donation Routes
    Route::get('/donation', [DonationController::class, 'index'])->name('donation.index');
    Route::post('/donation/convert', [DonationController::class, 'convert'])->name('donation.convert');
    Route::delete('/donation/{id}', [DonationController::class, 'destroy'])->name('donation.destroy');

    Route::post('/donations/{id}/redeem', [DonationController::class, 'redeem'])
        ->name('donation.redeem')
        ->middleware('auth');

    // ✅ New: Pickup route
    Route::post('/donations/{id}/pickup', [DonationController::class, 'pickup'])
        ->name('donation.pickup')
        ->middleware('auth');

    // ✅ Browse
    Route::get('/browse', [BrowseController::class, 'index'])->name('browse.index');

    // ✅ Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.readAll');

    // ✅ Meal Plan Routes
    Route::get('/mealplans', [MealPlanController::class, 'index'])->name('mealplans.index');
    Route::get('/mealplans/create', [MealPlanController::class, 'create'])->name('mealplans.create');
    Route::post('/mealplans', [MealPlanController::class, 'store'])->name('mealplans.store');
    Route::get('/mealplans/{mealPlan}/edit', [MealPlanController::class, 'edit'])->name('mealplans.edit');
    Route::put('/mealplans/{mealPlan}', [MealPlanController::class, 'update'])->name('mealplans.update');
    Route::delete('/mealplans/{mealPlan}', [MealPlanController::class, 'destroy'])->name('mealplans.destroy');
    Route::get('/mealplans/{mealPlan}/show', [MealPlanController::class, 'show'])->name('mealplans.show');
});

// ✅ Auth routes
require __DIR__.'/auth.php';
