<?php

use App\Http\Controllers\RetreatApprovalController;
use App\Http\Controllers\RetreatDashboardController;
use App\Http\Controllers\RetreatGroupController;
use App\Http\Controllers\RetreatRouteController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', RetreatDashboardController::class)->name('dashboard');
    Route::get('routes', [RetreatRouteController::class, 'index'])->name('retreat.routes');
    Route::get('routes/create', [RetreatRouteController::class, 'create'])->name('retreat.routes.create');
    Route::post('routes', [RetreatRouteController::class, 'store'])->name('retreat.routes.store');
    Route::get('routes/{retreatRoute}', [RetreatRouteController::class, 'show'])->name('retreat.routes.show');

    Route::get('approvals', [RetreatApprovalController::class, 'index'])->name('retreat.approvals');
    Route::post('approvals/{retreatRoute}/approve', [RetreatApprovalController::class, 'approve'])->name('retreat.approvals.approve');
    Route::post('approvals/{retreatRoute}/reject', [RetreatApprovalController::class, 'reject'])->name('retreat.approvals.reject');

    Route::get('groups', [RetreatGroupController::class, 'index'])->name('retreat.groups');
    Route::get('groups/create', [RetreatGroupController::class, 'create'])->name('retreat.groups.create');
    Route::post('groups', [RetreatGroupController::class, 'store'])->name('retreat.groups.store');
    Route::get('groups/{retreatGroup}', [RetreatGroupController::class, 'show'])->name('retreat.groups.show');
    Route::post('groups/{retreatGroup}/applications', [RetreatGroupController::class, 'apply'])->name('retreat.groups.applications.store');
    Route::post('groups/{retreatGroup}/applications/{application}/review', [RetreatGroupController::class, 'reviewApplication'])->name('retreat.groups.applications.review');
});

require __DIR__.'/settings.php';
