<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');

    Route::resource('novels', \App\Http\Controllers\NovelController::class);
    Route::put('novels/{novel}', [\App\Http\Controllers\NovelController::class, 'update'])->name('novels.update');
    Route::post('novels/{novel}/chapters', [\App\Http\Controllers\ChapterController::class, 'store'])->name('chapters.store');
    Route::put('novels/{novel}/chapters/{chapter}', [\App\Http\Controllers\ChapterController::class, 'update'])->name('chapters.update');
    Route::post('novels/{novel}/chapters/{chapter}/generate', [\App\Http\Controllers\ChapterController::class, 'generate'])->name('chapters.generate');
    Route::post('novels/{novel}/chapters/{chapter}/analyze', [\App\Http\Controllers\ChapterController::class, 'analyze'])->name('chapters.analyze');
    Route::post('novels/{novel}/chapters/{chapter}/suggest', [\App\Http\Controllers\ChapterController::class, 'suggest'])->name('chapters.suggest');
    Route::post('novels/{novel}/chapters/{chapter}/rewrite', [\App\Http\Controllers\ChapterController::class, 'rewrite'])->name('chapters.rewrite');
    Route::post('novels/{novel}/chapters/{chapter}/expand', [\App\Http\Controllers\ChapterController::class, 'expand'])->name('chapters.expand');
    Route::post('novels/{novel}/documents', [\App\Http\Controllers\SourceDocumentController::class, 'store'])->name('documents.store');
    Route::post('novels/{novel}/documents/link', [\App\Http\Controllers\SourceDocumentController::class, 'storeLink'])->name('documents.storeLink');
});

require __DIR__.'/settings.php';
