<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\GoogleAuthController;
use App\Http\Controllers\Api\AuthController;

Route::get('/', function () {
    return view('welcome');
});

// Google OAuth routes - must be in web.php for session support
Route::get('/api/auth/google', [GoogleAuthController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('/api/auth/google/callback', [GoogleAuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');

// Also handle callback without /api prefix (for Google Console redirect URI compatibility)
Route::get('/auth/google/callback', [GoogleAuthController::class, 'handleGoogleCallback'])->name('auth.google.callback.alt');

// Email Verification - Web route that returns HTML page
Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmailWeb'])->name('verification.verify');
