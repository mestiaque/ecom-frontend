<?php

use Illuminate\Support\Facades\Route;
use ME\Efront\Http\Controllers\Admin\ThemeController;
use ME\Http\Middleware\LocaleMiddleware;

// Admin pages for the storefront (metheme layout, URL prefix from me_prefix()). Route names start with "efront.admin.".
Route::group([
    'prefix' => me_prefix().'/storefront-theme',
    'as' => 'efront.admin.theme.',
    'middleware' => ['web', 'auth', LocaleMiddleware::class, 'activityLog'],
], function () {
    Route::get('/', [ThemeController::class, 'edit'])->name('edit');
    Route::put('/', [ThemeController::class, 'update'])->name('update');                       // Save Draft / Publish
    Route::post('/preview', [ThemeController::class, 'preview'])->name('preview');              // live preview (AJAX)
    Route::get('/preview/exit', [ThemeController::class, 'exitPreview'])->name('preview.exit');
    Route::delete('/draft', [ThemeController::class, 'discardDraft'])->name('draft.discard');
    Route::post('/presets/{preset}', [ThemeController::class, 'applyPreset'])->name('preset');
    Route::get('/export', [ThemeController::class, 'export'])->name('export');
    Route::post('/import', [ThemeController::class, 'import'])->name('import');
    Route::get('/versions/{version}/preview', [ThemeController::class, 'previewVersion'])->name('version.preview');
    Route::post('/versions/{version}/restore', [ThemeController::class, 'restore'])->name('version.restore');
    Route::post('/reset', [ThemeController::class, 'reset'])->name('reset');
});
