<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// The GET /api/user endpoint (Sanctum stateful, kept for compatibility)
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// NOTE: /api/tutors and all other protected routes live in web.php under
// the 'auth' middleware group so they use session-based authentication.
// The personal_access_tokens table's tokenable_id (bigint) is incompatible
// with the UUID user_id (char 36) — API token auth via Sanctum is not used.
