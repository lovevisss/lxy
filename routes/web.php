<?php

use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
    Route::inertia('routes', 'retreat/Routes')->name('retreat.routes');
    Route::inertia('routes/create', 'retreat/CreateRoute')->name('retreat.routes.create');
    Route::inertia('groups', 'retreat/Groups')->name('retreat.groups');
    Route::inertia('approvals', 'retreat/Approvals')->name('retreat.approvals');
});

require __DIR__.'/settings.php';
