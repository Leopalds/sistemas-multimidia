<?php 

use App\Http\Controllers\Api\MediaController;
use App\Http\Controllers\PersonController;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/media/{id}', [MediaController::class, 'find']);

Route::post('/media/{media}/processed', [MediaController::class, 'processed']);

// Rotas para pessoas
Route::patch('/people/{person}/name', [PersonController::class, 'updateName']);
Route::get('/people/search', [PersonController::class, 'search']);
