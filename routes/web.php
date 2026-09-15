<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Member\AvailabilityController;
use App\Http\Controllers\Member\PackageController;
use App\Http\Controllers\Member\ReservationController;
use App\Http\Controllers\Member\SafariController;
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
    Route::get('/availability', [AvailabilityController::class, 'index'])->name('member.availability');

    Route::get('/reservations', [ReservationController::class, 'index'])->name('member.reservations.index');
    Route::post('/reservations', [ReservationController::class, 'store'])->name('member.reservations.store');
    Route::delete('/reservations/{reservation}', [ReservationController::class, 'destroy'])->name('member.reservations.destroy');
    Route::post('/reservations/{reservation}/late-cancel', [ReservationController::class, 'lateCancel'])->name('member.reservations.late-cancel');

    Route::get('/safari-tours', [SafariController::class, 'index'])->name('member.safari.index');
    Route::post('/safari-tours/book', [SafariController::class, 'store'])->name('member.safari.store');

    Route::get('/packages', [PackageController::class, 'index'])->name('member.packages.index');
    Route::post('/packages/request', [PackageController::class, 'requestPurchase'])->name('member.packages.request');
});

Route::middleware(['auth', 'role:trainer|admin'])->prefix('trainer')->group(function () {
    //
});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(function () {
    //
});
