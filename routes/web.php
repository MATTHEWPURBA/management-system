<?php

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application.
| These routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group.
|
*/

// Basic welcome page
Route::get('/', function () {
    return view('welcome');
});

// Sanctum CSRF endpoint for SPA authentication
Route::get('/sanctum/csrf-cookie', function () {
    return response()->json(['message' => 'CSRF cookie set']);
});

// SPA fallback - This redirects all other requests to the Vue app for client-side routing
Route::get('{any}', function () {
    return view('welcome');
})->where('any', '.*');



Route::get('/test-log', function () {
    // Test multiple logging channels
    Log::channel('single')->info('Test single channel');
    Log::channel('api_activity')->info('Test API activity channel');
    Log::channel('stack')->info('Test stack channel');
    
    // Test default channel
    Log::info('Test default channel');
    
    return "Logging tests written. Check your log files.";
});
// routes/web.php