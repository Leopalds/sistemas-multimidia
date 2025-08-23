<?php 

use App\Http\Controllers\Api\MediaController;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/media/{id}', [MediaController::class, 'find']);

Route::post('/media/{media}/processed', [MediaController::class, 'processed']);
