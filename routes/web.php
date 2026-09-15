<?php

use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('throttle:10,1')->prefix('login')->group(function () {
    Route::post('/member', [LoginController::class, 'member'])->name('login.member');
    Route::post('/trainer', [LoginController::class, 'trainer'])->name('login.trainer');
    Route::post('/admin', [LoginController::class, 'admin'])->name('login.admin');
});

Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

// Role-gated route group skeletons — controllers land in Phases 2-4.
Route::middleware(['auth', 'role:member|admin'])->prefix('member')->group(function () {
    //
});

Route::middleware(['auth', 'role:trainer|admin'])->prefix('trainer')->group(function () {
    //
});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(function () {
    //
});
