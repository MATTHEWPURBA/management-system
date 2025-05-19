<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ActivityLogController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes (no authentication required)
// ==========================================

/**
 * Authentication Routes
 * 
 * POST /api/login - User login and token generation
 *   - Validates credentials
 *   - Checks if user is active
 *   - Returns authentication token and user info
 */
Route::post('/login', [AuthController::class, 'login']);

// Protected routes (authentication required)
// ==========================================
// All routes within this group require a valid Sanctum token
// The check.userstatus middleware ensures inactive users can't access routes
Route::middleware(['auth:sanctum', 'check.userstatus'])->group(function () {
    
    /**
     * Authentication Routes (authenticated)
     * 
     * POST /api/logout - User logout
     *   - Invalidates the current token
     *   - Logs the logout action
     */
    Route::post('/logout', [AuthController::class, 'logout']);
    
    /**
     * User Management Routes
     * 
     * Access restricted based on user role:
     * - GET /api/users - List all users (admin, manager only)
     * - GET /api/users/{id} - View specific user (admin, manager only)
     * - POST /api/users - Create new user (admin only)
     * - PUT /api/users/{id} - Update user (admin only)
     * - DELETE /api/users/{id} - Delete user (admin only)
     */
    Route::prefix('users')->group(function () {
        // Routes accessible to admin and manager
        Route::get('/', [UserController::class, 'index']);
        Route::get('/{id}', [UserController::class, 'show']);
        
        // Routes accessible to admin only
        Route::middleware('can:manage-users')->group(function () {
            Route::post('/', [UserController::class, 'store']);
            Route::put('/{id}', [UserController::class, 'update']);
            Route::delete('/{id}', [UserController::class, 'destroy']);
        });
    });
    
    /**
     * Task Management Routes
     * 
     * Access varies based on user role:
     * - GET /api/tasks - List tasks (filtered by role permissions)
     * - GET /api/tasks/{id} - View specific task (if permitted)
     * - POST /api/tasks - Create new task (all roles, but with assignment restrictions)
     * - PUT /api/tasks/{id} - Update task (if permitted)
     * - DELETE /api/tasks/{id} - Delete task (admin or creator only)
     * - GET /api/tasks/export - Export tasks to CSV (admin only)
     */
    Route::prefix('tasks')->group(function () {
        Route::get('/', [TaskController::class, 'index']);
        Route::get('/{id}', [TaskController::class, 'show']);
        Route::post('/', [TaskController::class, 'store']);
        Route::put('/{id}', [TaskController::class, 'update']);
        Route::delete('/{id}', [TaskController::class, 'destroy']);
        
        // Export route - Admin only
        Route::get('/export', [TaskController::class, 'export'])->middleware('can:export-tasks');
    });
    
    /**
     * Activity Log Routes
     * 
     * Access restricted to admin only:
     * - GET /api/logs - List activity logs
     * - GET /api/logs/{id} - View specific log entry
     */
    Route::prefix('logs')->middleware('can:view-logs')->group(function () {
        Route::get('/', [ActivityLogController::class, 'index']);
        Route::get('/{id}', [ActivityLogController::class, 'show']);
    });
});