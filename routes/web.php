<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClaimController;
use App\Http\Controllers\ItemReportController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');
Route::middleware('guest')->group(function () {
    Route::view('/register', 'auth.register')->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:6,1');
    Route::view('/login', 'auth.login')->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1');
});
Route::middleware('auth')->group(function () {
    Route::get('/items', [ItemReportController::class, 'index'])->name('items.index');
    Route::view('/items/create', 'items.create')->name('items.create');
    Route::post('/items', [ItemReportController::class, 'store'])->middleware('throttle:10,1')->name('items.store');
    Route::get('/my-reports', [ItemReportController::class, 'mine'])->name('items.mine');
    Route::get('/items/{report}/photo', [ItemReportController::class, 'photo'])->name('items.photo');
    Route::get('/items/{report}/edit', [ItemReportController::class, 'edit'])->name('items.edit');
    Route::patch('/items/{report}', [ItemReportController::class, 'update'])->middleware('throttle:10,1')->name('items.update');
    Route::get('/items/{report}', [ItemReportController::class, 'show'])->name('items.show');
    Route::post('/items/{report}/claims', [ClaimController::class, 'store'])->middleware('throttle:6,1')->name('claims.store');
    Route::get('/my-claims', [ClaimController::class, 'mine'])->name('claims.mine');
    Route::get('/admin/claims', [ClaimController::class, 'admin'])->name('admin.claims');
    Route::patch('/admin/claims/{claim}', [ClaimController::class, 'review'])->name('admin.claims.review');
    Route::post('/admin/reports/{report}/return', [ClaimController::class, 'returned'])->name('admin.return');
    Route::get('/admin/returns', [ClaimController::class, 'history'])->name('admin.history');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications');
    Route::post('/notifications/read', [NotificationController::class, 'read'])->name('notifications.read');
    Route::get('/admin/reports', [ItemReportController::class, 'admin'])->name('admin.reports');
    Route::patch('/admin/reports/{report}', [ItemReportController::class, 'review'])->name('admin.review');
    Route::view('/dashboard', 'dashboard')->name('dashboard');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});
