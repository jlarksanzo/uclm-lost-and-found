<?php
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ItemReportController;
use Illuminate\Support\Facades\Route;
Route::view('/', 'welcome')->name('home');
Route::middleware('guest')->group(function () {
    Route::view('/register', 'auth.register')->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:6,1');
    Route::view('/login', 'auth.login')->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1');
});
Route::middleware('auth')->group(function () {
    Route::get('/items', [ItemReportController::class,'index'])->name('items.index');
    Route::view('/items/create', 'items.create')->name('items.create');
    Route::post('/items', [ItemReportController::class,'store'])->middleware('throttle:10,1')->name('items.store');
    Route::get('/my-reports', [ItemReportController::class,'mine'])->name('items.mine');
    Route::get('/items/{report}/photo', [ItemReportController::class,'photo'])->name('items.photo');
    Route::get('/admin/reports', [ItemReportController::class,'admin'])->name('admin.reports');
    Route::patch('/admin/reports/{report}', [ItemReportController::class,'review'])->name('admin.review');
    Route::view('/dashboard', 'dashboard')->name('dashboard');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});
