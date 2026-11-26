<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProfileController;
use Illuminate\Support\Facades\Route;

// ─── Publik (tidak perlu token) ───────────────────────────────────────────────
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);

// ─── Terproteksi (wajib Header: Authorization: Bearer <token>) ────────────────
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::get('/profile',  [AuthController::class, 'profile']);
    Route::post('/logout',  [AuthController::class, 'logout']);

    // Update profil
    Route::put('/profile',             [ProfileController::class, 'updateProfile']);
    Route::put('/profile/password',    [ProfileController::class, 'updatePassword']);
    Route::post('/profile/photo',      [ProfileController::class, 'updatePhoto']);
});
