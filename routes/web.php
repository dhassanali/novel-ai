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
    Route::post('novels/{novel}/cover', [\App\Http\Controllers\NovelController::class, 'uploadCover'])->name('novels.uploadCover');
    Route::delete('novels/{novel}/cover', [\App\Http\Controllers\NovelController::class, 'deleteCover'])->name('novels.deleteCover');
    Route::get('novels/{novel}/export', [\App\Http\Controllers\NovelController::class, 'export'])->name('novels.export');

    Route::scopeBindings()->group(function () {
        Route::post('novels/{novel}/chapters', [\App\Http\Controllers\ChapterController::class, 'store'])->name('chapters.store');
        Route::put('novels/{novel}/chapters/{chapter}', [\App\Http\Controllers\ChapterController::class, 'update'])->name('chapters.update');
        Route::post('novels/{novel}/chapters/{chapter}/generate', [\App\Http\Controllers\ChapterController::class, 'generate'])->name('chapters.generate');
        Route::post('novels/{novel}/chapters/{chapter}/analyze', [\App\Http\Controllers\ChapterController::class, 'analyze'])->name('chapters.analyze');
        Route::post('novels/{novel}/chapters/{chapter}/suggest', [\App\Http\Controllers\ChapterController::class, 'suggest'])->name('chapters.suggest');
        Route::post('novels/{novel}/chapters/{chapter}/rewrite', [\App\Http\Controllers\ChapterController::class, 'rewrite'])->name('chapters.rewrite');
        Route::post('novels/{novel}/chapters/{chapter}/expand', [\App\Http\Controllers\ChapterController::class, 'expand'])->name('chapters.expand');

        Route::post('novels/{novel}/characters', [\App\Http\Controllers\CharacterController::class, 'store'])->name('characters.store');
        Route::put('novels/{novel}/characters/{character}', [\App\Http\Controllers\CharacterController::class, 'update'])->name('characters.update');
        Route::delete('novels/{novel}/characters/{character}', [\App\Http\Controllers\CharacterController::class, 'destroy'])->name('characters.destroy');

        Route::post('novels/{novel}/locations', [\App\Http\Controllers\LocationController::class, 'store'])->name('locations.store');
        Route::put('novels/{novel}/locations/{location}', [\App\Http\Controllers\LocationController::class, 'update'])->name('locations.update');
        Route::delete('novels/{novel}/locations/{location}', [\App\Http\Controllers\LocationController::class, 'destroy'])->name('locations.destroy');
    });

    Route::post('novels/{novel}/documents', [\App\Http\Controllers\SourceDocumentController::class, 'store'])->name('documents.store');
    Route::post('novels/{novel}/documents/link', [\App\Http\Controllers\SourceDocumentController::class, 'storeLink'])->name('documents.storeLink');

    Route::post('novels/{novel}/brainstorm', [\App\Http\Controllers\BrainstormController::class, 'generate'])->name('novels.brainstorm');
});

require __DIR__.'/settings.php';
