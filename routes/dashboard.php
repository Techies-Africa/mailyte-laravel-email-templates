<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Mailyte\EmailTemplates\Http\Controllers\DashboardController;

Route::get('/', [DashboardController::class, 'index'])->name('mailyte.index');
Route::get('/usage', [DashboardController::class, 'usage'])->name('mailyte.usage');
Route::get('/t/{slug}', [DashboardController::class, 'show'])->name('mailyte.show');
Route::get('/t/{slug}/preview', [DashboardController::class, 'preview'])->name('mailyte.preview');
Route::post('/t/{slug}/render', [DashboardController::class, 'renderJson'])->name('mailyte.render');
Route::post('/t/{slug}/send', [DashboardController::class, 'send'])->name('mailyte.send');

// Serves the bundled social icons for the preview gallery, so the footer looks
// the way it will look once `vendor:publish --tag=mailyte-assets` has put the
// same files behind the application's own public URL.
Route::get('/assets/{group}/{icon}', [DashboardController::class, 'asset'])
    ->where('group', 'social|brand')
    ->where('icon', '[a-z0-9-]+\.png')
    ->name('mailyte.asset');
