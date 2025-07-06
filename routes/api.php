<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\API\FileController;
use App\Http\Controllers\API\RepositoryController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth.jwt')->group(function () {
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/repositories', [RepositoryController::class, 'index']);
    Route::post('/repositories', [RepositoryController::class, 'store']);
    Route::put('/repositories/{id}', [RepositoryController::class, 'update']);
    Route::delete('/repositories/{id}', [RepositoryController::class, 'destroy']);
    Route::post('/repositories/{id}/files', [FileController::class, 'upload']);
    Route::get('/repositories/{id}/files', [FileController::class, 'listFiles']);
    Route::get('/files/{fileId}/versions', [FileController::class, 'listVersions']);
    Route::get('/files/{fileId}/versions/{version}', [FileController::class, 'downloadVersion']);
    
    Route::post('/repositories/{id}/assign-role', [RepositoryController::class, 'assignRole']);
    Route::get('/users', [RepositoryController::class, 'listUsers']);
    Route::get('/files/{fileId}/compare/{versionA}/{versionB}', [FileController::class, 'compareVersions']);

    Route::get('/files/{fileId}/preview', [FileController::class, 'preview']);
    Route::get('/files/{fileId}/versions/{versionNumber}/preview', [FileController::class, 'previewVersion']);
    Route::get('/search-files', [FileController::class, 'searchFiles']);
});
