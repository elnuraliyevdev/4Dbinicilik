<?php

use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FamilyController as AdminFamilyController;
use App\Http\Controllers\Admin\MemberController as AdminMemberController;
use App\Http\Controllers\Admin\PackageController as AdminPackageController;
use App\Http\Controllers\Admin\PurchaseRequestController;
use App\Http\Controllers\Admin\SafariTourController as AdminSafariTourController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Auth\ClaimAccountController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\SessionController;
use App\Http\Controllers\Member\AvailabilityController;
use App\Http\Controllers\Member\PackageController;
use App\Http\Controllers\Member\ReservationController;
use App\Http\Controllers\Member\SafariController;
use App\Http\Controllers\Trainer\AttendanceController;
use App\Http\Controllers\Trainer\FeedbackController;
use App\Http\Controllers\Trainer\ScheduleController;
use App\Http\Controllers\Trainer\SlotController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('app');
});

Route::get('/me', [SessionController::class, 'me'])->name('session.me');
Route::get('/trainer-options', [SessionController::class, 'trainerOptions'])->name('session.trainer-options');

Route::middleware('throttle:10,1')->prefix('login')->group(function () {
    Route::post('/member', [LoginController::class, 'member'])->name('login.member');
    Route::post('/trainer', [LoginController::class, 'trainer'])->name('login.trainer');
    Route::post('/admin', [LoginController::class, 'admin'])->name('login.admin');
});

Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('signed')->prefix('claim-account')->group(function () {
    Route::get('/{user}', [ClaimAccountController::class, 'show'])->name('claim-account.show');
    Route::post('/{user}', [ClaimAccountController::class, 'store'])->name('claim-account.store');
});

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
    Route::get('/schedule', [ScheduleController::class, 'index'])->name('trainer.schedule');
    Route::post('/reservations/{reservation}/attendance', [AttendanceController::class, 'store'])->name('trainer.attendance');
    Route::post('/feedback', [FeedbackController::class, 'store'])->name('trainer.feedback');
    Route::post('/slots/toggle', [SlotController::class, 'toggle'])->name('trainer.slots.toggle');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');

    Route::get('/reservations/today', [AdminAttendanceController::class, 'today'])->name('admin.attendance.today');
    Route::post('/reservations/{reservation}/attendance', [AdminAttendanceController::class, 'store'])->name('admin.attendance.store');

    Route::get('/members', [AdminMemberController::class, 'index'])->name('admin.members.index');
    Route::get('/members/{member}', [AdminMemberController::class, 'show'])->name('admin.members.show');
    Route::patch('/members/{member}', [AdminMemberController::class, 'update'])->name('admin.members.update');
    Route::post('/members/{member}/credits', [AdminMemberController::class, 'adjustCredits'])->name('admin.members.credits');
    Route::post('/members/{member}/claim-link', [AdminMemberController::class, 'sendClaimLink'])->name('admin.members.claim-link');

    Route::get('/families', [AdminFamilyController::class, 'index'])->name('admin.families.index');
    Route::post('/families', [AdminFamilyController::class, 'store'])->name('admin.families.store');
    Route::post('/families/{family}/members', [AdminFamilyController::class, 'addMember'])->name('admin.families.members.add');
    Route::delete('/families/{family}/members/{familyMember}', [AdminFamilyController::class, 'removeMember'])->name('admin.families.members.remove');

    Route::get('/packages', [AdminPackageController::class, 'index'])->name('admin.packages.index');
    Route::post('/packages', [AdminPackageController::class, 'store'])->name('admin.packages.store');
    Route::patch('/packages/{package}', [AdminPackageController::class, 'update'])->name('admin.packages.update');

    Route::get('/safari-tours', [AdminSafariTourController::class, 'index'])->name('admin.safari-tours.index');
    Route::post('/safari-tours', [AdminSafariTourController::class, 'store'])->name('admin.safari-tours.store');
    Route::patch('/safari-tours/{safariTour}', [AdminSafariTourController::class, 'update'])->name('admin.safari-tours.update');

    Route::get('/purchase-requests', [PurchaseRequestController::class, 'index'])->name('admin.purchase-requests.index');
    Route::post('/purchase-requests/{purchaseRequest}/approve', [PurchaseRequestController::class, 'approve'])->name('admin.purchase-requests.approve');
    Route::post('/purchase-requests/{purchaseRequest}/reject', [PurchaseRequestController::class, 'reject'])->name('admin.purchase-requests.reject');

    Route::get('/settings', [SettingController::class, 'index'])->name('admin.settings.index');
    Route::put('/settings/{key}', [SettingController::class, 'update'])->name('admin.settings.update');

    Route::get('/audit-log', [AuditLogController::class, 'index'])->name('admin.audit-log.index');
    Route::get('/audit-log/export', [AuditLogController::class, 'export'])->name('admin.audit-log.export');
});
