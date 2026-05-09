<?php

declare(strict_types=1);

use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ProfileController as ApiProfileController;
use App\Http\Controllers\Api\RequestController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\SessionController;
use App\Http\Controllers\Api\TutorController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function (): void {
    Route::get('/profile',    [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile',  [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Protected API routes (session-based auth, 60 req/min per user).
Route::middleware(['auth', 'throttle:60,1'])->prefix('api')->group(function (): void {
    Route::get('/tutors', [TutorController::class, 'index']);

    Route::get('/profile',   [ApiProfileController::class, 'show']);
    Route::patch('/profile', [ApiProfileController::class, 'update']);

    // ── Tutoring requests — one route per lifecycle action ──
    Route::get('/requests',  [RequestController::class, 'index']);
    Route::post('/requests', [RequestController::class, 'store']);
    // The split endpoints replace the old "PATCH /requests/{id} {action: …}"
    // dispatch — frontend routes each user action to the URL below directly.
    Route::patch('/requests/{id}/accept',          [RequestController::class, 'accept']);
    Route::patch('/requests/{id}/claim',           [RequestController::class, 'claim']);
    Route::patch('/requests/{id}/decline',         [RequestController::class, 'decline']);
    Route::patch('/requests/{id}/counter-propose', [RequestController::class, 'counterPropose']);
    Route::patch('/requests/{id}/student-accept',  [RequestController::class, 'studentAccept']);
    Route::patch('/requests/{id}/student-decline', [RequestController::class, 'studentDecline']);
    Route::patch('/requests/{id}/cancel',          [RequestController::class, 'cancel']);

    Route::post('/sessions/broadcast', [RequestController::class, 'broadcastSession']);

    Route::get('/notifications',         [NotificationController::class, 'index']);
    Route::patch('/notifications/read',  [NotificationController::class, 'markAllRead']);

    Route::get('/reviews',  [ReviewController::class, 'index']);
    Route::post('/reviews', [ReviewController::class, 'store']);

    Route::get('/sessions',            [SessionController::class, 'index']);
    Route::get('/sessions/open',       [SessionController::class, 'open']);
    Route::post('/sessions/{id}/join', [SessionController::class, 'join']);
    Route::patch('/sessions/{id}',     [SessionController::class, 'update']);

    Route::get('/tutors/{id}',           [TutorController::class, 'show']);
    Route::get('/courses/{code}/topics', [TutorController::class, 'topics']);

    Route::patch('/user/profile',  [ApiProfileController::class, 'updatePersonal']);
    Route::post('/user/password',  [ApiProfileController::class, 'changePassword']);
    Route::post('/user/photo',     [ApiProfileController::class, 'uploadPhoto']);
    Route::delete('/user/photo',   [ApiProfileController::class, 'deletePhoto']);
});

require __DIR__ . '/auth.php';
