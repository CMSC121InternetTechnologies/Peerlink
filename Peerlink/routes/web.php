<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Api\RequestController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\SessionController;
use App\Http\Controllers\Api\TutorController;
use App\Http\Controllers\Api\ProfileApiController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Protected API routes (session-based auth, 60 req/min per user)
Route::middleware(['auth', 'throttle:60,1'])->prefix('api')->group(function () {
    Route::get('/tutors',                [TutorController::class, 'index']);

    Route::get('/profile',              [ProfileApiController::class, 'show']);
    Route::patch('/profile',            [ProfileApiController::class, 'update']);

    Route::get('/requests',              [RequestController::class, 'index']);
    Route::post('/requests',             [RequestController::class, 'store']);
    Route::patch('/requests/{id}',       [RequestController::class, 'respond']);
    Route::post('/sessions/broadcast',   [RequestController::class, 'broadcastSession']);

    Route::get('/notifications',         [NotificationController::class, 'index']);
    Route::patch('/notifications/read',  [NotificationController::class, 'markAllRead']);

    Route::get('/reviews',               [ReviewController::class, 'index']);
    Route::post('/reviews',              [ReviewController::class, 'store']);

    Route::get('/sessions',              [SessionController::class, 'index']);
    Route::get('/sessions/open',         [SessionController::class, 'open']);
    Route::post('/sessions/{id}/join',   [SessionController::class, 'join']);
    Route::patch('/sessions/{id}',       [SessionController::class, 'update']);

    Route::get('/tutors/{id}',           [TutorController::class, 'show']);
    Route::get('/courses/{code}/topics', [TutorController::class, 'topics']);

    Route::patch('/user/profile',        [ProfileApiController::class, 'updatePersonal']);
    Route::post('/user/password',        [ProfileApiController::class, 'changePassword']);
    Route::post('/user/photo',           [ProfileApiController::class, 'uploadPhoto']);
});

require __DIR__.'/auth.php';