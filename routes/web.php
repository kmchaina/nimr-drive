<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use Inertia\Inertia;

// Home page is also the login page
Route::get('/', function () {
    if (session('user')) {
        return redirect('/dashboard');
    }

    return Inertia::render('Home', [
        'appName' => config('app.name', 'NIMR Drive'),
    ]);
})->name('login');

// Login redirect (for compatibility)
Route::get('/login', function () {
    return redirect('/');
});

// Handle login POST
Route::post('/login', [AuthController::class, 'login']);

// Session check route
Route::get('/api/session', [AuthController::class, 'checkSession']);

// Protected routes (require authentication)
Route::middleware(['user.isolation'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile');

    // File management API routes
    Route::prefix('api')->group(function () {
        // File operations with rate limiting (60 requests per minute)
        Route::middleware('throttle:60,1')->group(function () {
            Route::get('/files', [App\Http\Controllers\FileController::class, 'index']);
            Route::post('/files/folder', [App\Http\Controllers\FileController::class, 'createFolder']);
            Route::put('/files/rename', [App\Http\Controllers\FileController::class, 'rename']);
            Route::delete('/files/delete', [App\Http\Controllers\FileController::class, 'delete']);
            Route::delete('/files/batch-delete', [App\Http\Controllers\FileController::class, 'batchDelete']);
            Route::get('/files/download', [App\Http\Controllers\FileController::class, 'download']);
            Route::get('/files/info', [App\Http\Controllers\FileController::class, 'info']);
            Route::post('/files/upload', [App\Http\Controllers\FileController::class, 'upload']);
            Route::get('/quota', [App\Http\Controllers\FileController::class, 'quota']);
            Route::post('/quota/recalculate', [App\Http\Controllers\FileController::class, 'recalculateQuota']);

            // Search routes
            Route::get('/search', [App\Http\Controllers\SearchController::class, 'search']);
        });

        // Upload routes with higher rate limit for chunked uploads
        // 2GB file with 2MB chunks = 1024 chunks per file, need generous limit
        Route::middleware('throttle:2000,1')->group(function () {
            Route::post('/upload/chunk', [App\Http\Controllers\UploadController::class, 'uploadChunk']);
            Route::get('/upload/progress', [App\Http\Controllers\UploadController::class, 'getProgress']);
            Route::post('/upload/cancel', [App\Http\Controllers\UploadController::class, 'cancelUpload']);
        });
    });

    // Admin routes (require admin role)
    Route::middleware(['admin'])->prefix('admin')->group(function () {
        Route::get('/', [App\Http\Controllers\AdminController::class, 'index'])->name('admin.index');
        Route::put('/users/{user}/quota', [App\Http\Controllers\AdminController::class, 'updateQuota']);
        Route::post('/users/{user}/toggle-admin', [App\Http\Controllers\AdminController::class, 'toggleAdmin']);
        Route::post('/users/{user}/recalculate', [App\Http\Controllers\AdminController::class, 'recalculateUsage']);
        Route::post('/users/bulk-quota', [App\Http\Controllers\AdminController::class, 'bulkUpdateQuota']);
    });
});
