<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\BrowseController;

Route::get('/', [BrowseController::class, 'index'])->name('browse');
Route::get('/browse', [BrowseController::class, 'index']);
Route::get('/chat', [ChatController::class, 'index'])->name('chat');
Route::get('/dashboard', function () {
    return view('dashboard');
})->name('dashboard');

// Admin routes
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('index');
    Route::get('/create', [AdminController::class, 'create'])->name('create');
    Route::post('/', [AdminController::class, 'store'])->name('store');
    Route::get('/{id}/edit', [AdminController::class, 'edit'])->name('edit');
    Route::put('/{id}', [AdminController::class, 'update'])->name('update');
    Route::delete('/{id}', [AdminController::class, 'destroy'])->name('destroy');

    // Import
    Route::get('/import', [AdminController::class, 'importForm'])->name('import');
    Route::post('/import', [AdminController::class, 'import'])->name('import.process');

    // Scraper
    Route::get('/scraper', [AdminController::class, 'scraperForm'])->name('scraper');
    Route::post('/scraper/import', [AdminController::class, 'importJson'])->name('scraper.import');

    // Scraper Test
    Route::get('/scraper/test', [AdminController::class, 'scraperTestForm'])->name('scraper.test.form');
    Route::post('/scraper/test', [AdminController::class, 'runScraperTest'])->name('scraper.test');
    Route::post('/scraper/import-results', [AdminController::class, 'importScrapedResults'])->name('scraper.import-results');
});
