<?php

use App\Http\Controllers\RetreatApprovalController;
use App\Http\Controllers\RetreatDashboardController;
use App\Http\Controllers\RetreatGroupController;
use App\Http\Controllers\RetreatRouteController;
use App\Http\Controllers\RetreatRouteImportController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', RetreatDashboardController::class)->name('dashboard');
    Route::get('routes', [RetreatRouteController::class, 'index'])->name('retreat.routes');
    Route::get('routes/create', [RetreatRouteController::class, 'create'])->name('retreat.routes.create');
    Route::get('routes/import', [RetreatRouteImportController::class, 'index'])->name('retreat.routes.import');
    Route::get('routes/import/template', [RetreatRouteImportController::class, 'template'])->name('retreat.routes.import.template');
    Route::post('routes/import', [RetreatRouteImportController::class, 'store'])->name('retreat.routes.import.store');
    Route::post('routes', [RetreatRouteController::class, 'store'])->name('retreat.routes.store');
    Route::post('routes/{retreatRoute}/reviews', [RetreatRouteController::class, 'storeReview'])->name('retreat.routes.reviews.store');
    Route::get('routes/{retreatRoute}', [RetreatRouteController::class, 'show'])->name('retreat.routes.show');

    Route::get('approvals', [RetreatApprovalController::class, 'index'])->name('retreat.approvals');
    Route::post('approvals/{retreatRoute}/approve', [RetreatApprovalController::class, 'approve'])->name('retreat.approvals.approve');
    Route::post('approvals/{retreatRoute}/reject', [RetreatApprovalController::class, 'reject'])->name('retreat.approvals.reject');

    Route::get('groups', [RetreatGroupController::class, 'index'])->name('retreat.groups');
    Route::get('groups/create', [RetreatGroupController::class, 'create'])->name('retreat.groups.create');
    Route::post('groups', [RetreatGroupController::class, 'store'])->name('retreat.groups.store');
    Route::get('groups/{retreatGroup}', [RetreatGroupController::class, 'show'])->name('retreat.groups.show');
    Route::get('groups/{retreatGroup}/edit', [RetreatGroupController::class, 'edit'])->name('retreat.groups.edit');
    Route::put('groups/{retreatGroup}', [RetreatGroupController::class, 'update'])->name('retreat.groups.update');
    Route::get('groups/{retreatGroup}/attachment', [RetreatGroupController::class, 'downloadAttachment'])->name('retreat.groups.attachment');
    Route::get('groups/{retreatGroup}/wechat-qr-code', [RetreatGroupController::class, 'showWechatQrCode'])->name('retreat.groups.wechat-qr-code');
    Route::post('groups/{retreatGroup}/applications', [RetreatGroupController::class, 'apply'])->name('retreat.groups.applications.store');
    Route::post('groups/{retreatGroup}/members', [RetreatGroupController::class, 'addMember'])->name('retreat.groups.members.store');
    Route::put('groups/{retreatGroup}/leader-family', [RetreatGroupController::class, 'updateLeaderFamily'])->name('retreat.groups.leader-family.update');
    Route::post('groups/{retreatGroup}/applications/{application}/review', [RetreatGroupController::class, 'reviewApplication'])->name('retreat.groups.applications.review');
    Route::post('groups/{retreatGroup}/status', [RetreatGroupController::class, 'changeStatus'])->name('retreat.groups.status');
    Route::post('groups/{retreatGroup}/final-confirmation', [RetreatGroupController::class, 'confirmParticipation'])->name('retreat.groups.final-confirmation');
    Route::post('groups/{retreatGroup}/final-confirmation/remind', [RetreatGroupController::class, 'remindFinalConfirmation'])->name('retreat.groups.final-confirmation.remind');
});

require __DIR__.'/settings.php';
