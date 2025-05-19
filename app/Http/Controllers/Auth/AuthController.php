<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * Authentication Controller
 * 
 * Handles user authentication, token generation, and logout functionality.
 * Implements Sanctum token-based authentication for API access.
 */
class AuthController extends Controller
{
    /**
     * Handle user login and token generation
     * 
     * This method:
     * 1. Validates the incoming login credentials
     * 2. Attempts authentication with the provided credentials
     * 3. Checks if the user is active (business rule: inactive users can't login)
     * 4. Creates a personal access token for API usage
     * 5. Logs the login activity
     * 6. Returns a success response with the user and token information
     * 
     * @param Request $request The HTTP request containing login credentials
     * @return \Illuminate\Http\JsonResponse Response with token, user info, or error message
     */
    public function login(Request $request)
    {
        // Validate input data
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Return validation errors if validation fails
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Attempt to authenticate the user
        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid login credentials'
            ], 401);
        }

        // Get the authenticated user
        $user = User::where('email', $request->email)->first();

        // Check if the user is active
        if (!$user->isActive()) {
            // If inactive, return error and don't generate a token
            Auth::logout();
            return response()->json([
                'success' => false,
                'message' => 'User account is inactive'
            ], 403);
        }

        // Create a new token for the user
        $token = $user->createToken('auth_token')->plainTextToken;

        // Log the successful login
        ActivityLog::logUserAction(
            $user->id,
            'user_login',
            "User {$user->name} logged in"
        );

        // Return a successful response with token and user information
        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                ],
                'access_token' => $token,
                'token_type' => 'Bearer',
            ]
        ], 200);
    }

    /**
     * Handle user logout
     * 
     * This method:
     * 1. Gets the currently authenticated user
     * 2. Deletes the current access token (logging the user out of this session only)
     * 3. Logs the logout activity
     * 4. Returns a success response
     * 
     * @param Request $request The HTTP request (requires authenticated user)
     * @return \Illuminate\Http\JsonResponse Success response
     */
    public function logout(Request $request)
    {
        // Get the authenticated user
        $user = $request->user();
        
        // Log the logout action
        ActivityLog::logUserAction(
            $user->id,
            'user_logout',
            "User {$user->name} logged out"
        );
        
        // Delete the current token that was used for auth
        $user->currentAccessToken()->delete();
        
        // Return success response
        return response()->json([
            'success' => true,
            'message' => 'Successfully logged out'
        ]);
    }
}

// app/http/controllers/auth/authcontroller.php