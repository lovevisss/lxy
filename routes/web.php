<?php

use App\Http\Controllers\CasAuthController;
use App\Http\Controllers\RetreatApprovalController;
use App\Http\Controllers\RetreatDashboardController;
use App\Http\Controllers\RetreatGroupController;
use App\Http\Controllers\RetreatPermissionController;
use App\Http\Controllers\RetreatRouteController;
use App\Http\Controllers\RetreatRouteImportController;
use App\Http\Middleware\EnsureRetreatEligible;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard')->name('home');

// CAS must be able to replace an existing local session when the user switches accounts.
Route::get('auth/cas', [CasAuthController::class, 'redirect'])->name('auth.cas.redirect');
Route::get('auth/cas/callback', [CasAuthController::class, 'callback'])->name('auth.cas.callback');
Route::get('auth/cas/logged-out', [CasAuthController::class, 'loggedOut'])->name('auth.cas.logged-out');
Route::match(['get', 'post'], 'auth/cas/slo', [CasAuthController::class, 'singleLogout'])
    ->name('auth.cas.slo');
Route::post('auth/cas/logout', [CasAuthController::class, 'logout'])
    ->middleware('auth')
    ->name('auth.cas.logout');

Route::middleware(['auth', 'verified', EnsureRetreatEligible::class])->group(function () {
    Route::get('dashboard', RetreatDashboardController::class)->name('dashboard');
    Route::get('routes', [RetreatRouteController::class, 'index'])->name('retreat.routes');
    Route::get('routes/create', [RetreatRouteController::class, 'create'])->name('retreat.routes.create');
    Route::get('routes/import', [RetreatRouteImportController::class, 'index'])->name('retreat.routes.import');
    Route::get('routes/import/template', [RetreatRouteImportController::class, 'template'])->name('retreat.routes.import.template');
    Route::post('routes/import/pdf', [RetreatRouteImportController::class, 'parsePdf'])->name('retreat.routes.import.pdf');
    Route::post('routes/import', [RetreatRouteImportController::class, 'store'])->name('retreat.routes.import.store');
    Route::post('routes', [RetreatRouteController::class, 'store'])->name('retreat.routes.store');
    Route::post('routes/{retreatRoute}/reviews', [RetreatRouteController::class, 'storeReview'])->name('retreat.routes.reviews.store');
    Route::get('routes/{retreatRoute}/attachment', [RetreatRouteController::class, 'downloadAttachment'])->name('retreat.routes.attachment');
    Route::get('routes/{retreatRoute}', [RetreatRouteController::class, 'show'])->name('retreat.routes.show');

    Route::get('approvals', [RetreatApprovalController::class, 'index'])->name('retreat.approvals');
    Route::post('approvals/{retreatRoute}/approve', [RetreatApprovalController::class, 'approve'])->name('retreat.approvals.approve');
    Route::post('approvals/{retreatRoute}/reject', [RetreatApprovalController::class, 'reject'])->name('retreat.approvals.reject');
    Route::post('group-approvals/{approvalNode}/review', [RetreatApprovalController::class, 'reviewGroup'])->name('retreat.group-approvals.review');

    Route::get('permissions', [RetreatPermissionController::class, 'index'])->name('retreat.permissions');
    Route::put('permissions/users/{user}', [RetreatPermissionController::class, 'update'])->name('retreat.permissions.update');

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
    Route::put('groups/{retreatGroup}/applications/{application}/family', [RetreatGroupController::class, 'updateApplicationFamily'])->name('retreat.groups.applications.family.update');
    Route::post('groups/{retreatGroup}/applications/{application}/review', [RetreatGroupController::class, 'reviewApplication'])->name('retreat.groups.applications.review');
    Route::post('groups/{retreatGroup}/status', [RetreatGroupController::class, 'changeStatus'])->name('retreat.groups.status');
    Route::post('groups/{retreatGroup}/submit-approval', [RetreatGroupController::class, 'submitApproval'])->name('retreat.groups.approval.submit');
    Route::delete('groups/{retreatGroup}/members/{application}', [RetreatGroupController::class, 'removeMember'])->name('retreat.groups.members.destroy');
    Route::post('groups/{retreatGroup}/final-confirmation', [RetreatGroupController::class, 'confirmParticipation'])->name('retreat.groups.final-confirmation');
    Route::post('groups/{retreatGroup}/final-confirmation/remind', [RetreatGroupController::class, 'remindFinalConfirmation'])->name('retreat.groups.final-confirmation.remind');
});

require __DIR__.'/settings.php';
