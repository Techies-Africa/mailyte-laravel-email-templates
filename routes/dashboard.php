<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Mailyte\EmailTemplates\Http\Controllers\DashboardController;

Route::get('/', [DashboardController::class, 'index'])->name('mailyte.index');
Route::get('/t/{slug}', [DashboardController::class, 'show'])->name('mailyte.show');
Route::get('/t/{slug}/preview', [DashboardController::class, 'preview'])->name('mailyte.preview');
Route::post('/t/{slug}/send', [DashboardController::class, 'send'])->name('mailyte.send');
