<?php

use App\Http\Controllers\MediaController;
use App\Http\Controllers\PersonController;
use App\Http\Controllers\ProfileController;
use App\Jobs\ProcessMedia;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Route::get('/', function () {
//     return Inertia::render('Welcome', [
//         'canLogin' => Route::has('login'),
//         'canRegister' => Route::has('register'),
//         'laravelVersion' => Application::VERSION,
//         'phpVersion' => PHP_VERSION,
//     ]);
// });

Route::get('/enqueue/{mediaId}', function (int $mediaId){
    ProcessMedia::dispatchRaw($mediaId, queue: 'face');
    return response()->json(['queued' => true]);
});

Route::get('/', function () {
    return redirect()->route('media.index');
})->name('home');

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::resource('media', MediaController::class)
    ->parameters(['media' => 'media'])
    ->only(['index', 'create', 'store', 'show', 'destroy']);

Route::resource('people', PersonController::class)
    ->parameters(['people' => 'person'])
    ->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);

// Rotas adicionais para pessoas
Route::patch('/people/{person}/name', [PersonController::class, 'updateName'])->name('people.updateName');
Route::get('/people/search', [PersonController::class, 'search'])->name('people.search');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
