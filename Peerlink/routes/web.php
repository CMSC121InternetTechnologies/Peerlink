<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;

/*
|--------------------------------------------------------------------------
| Public Routes (No Wrapper Needed)
|--------------------------------------------------------------------------
| Anyone can see these pages, whether logged in or out.
*/
Route::get('/', function () {
    return view('index'); // e.g., Your application's landing page
});

/*
|--------------------------------------------------------------------------
| The "Public Route Wrapper"
|--------------------------------------------------------------------------
| Only logged-OUT guests can see these. Logged-in users are redirected.
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'authenticate']);
    
    Route::get('/register', [RegisterController::class, 'show'])->name('register');
    Route::post('/register', [RegisterController::class, 'store']);
});

/*
|--------------------------------------------------------------------------
| The "Protected Route Wrapper"
|--------------------------------------------------------------------------
| Only logged-IN users can see these. Logged-out users are redirected to /login.
*/
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Core application features go here
   
});
