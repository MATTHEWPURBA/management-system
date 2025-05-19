<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * UserController - Handles API endpoints for User management
 * 
 * This controller provides the following endpoints:
 * - GET /api/users - List all users (admin, manager only)
 * - GET /api/users/{id} - Get a specific user (admin, manager only)
 * - POST /api/users - Create a new user (admin only)
 * - PUT /api/users/{id} - Update a user (admin only)
 * - DELETE /api/users/{id} - Delete a user (admin only)
 * 
 * It delegates business logic to the UserService and focuses on request
 * handling, validation, and response formatting.
 */
class UserController extends Controller
{
    /**
     * The user service instance.
     *
     * @var UserService
     */
    protected $userService;

    /**
     * Create a new controller instance.
     *
     * @param UserService $userService Injected service for business logic
     */
    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Display a listing of the users.
     * Accessible by: admin, manager
     *
     * @param Request $request The HTTP request
     * @return \Illuminate\Http\JsonResponse Response with users list or error
     */
    public function index(Request $request)
    {
        try {
            // Get the current user
            $currentUser = $request->user();
            
            // Get users through the service (handles authorization)
            $users = $this->userService->getUsers($currentUser);
            
            // Return successful response with users
            return response()->json([
                'success' => true,
                'data' => $users
            ]);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 403);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching users.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified user.
     * Accessible by: admin, manager
     *
     * @param Request $request The HTTP request
     * @param string $id The ID of the user to show
     * @return \Illuminate\Http\JsonResponse Response with user or error
     */
    public function show(Request $request, string $id)
    {
        try {
            // Find the user
            $user = User::find($id);
            
            // Check if user exists
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }
            
            // Check authorization
            if (!$request->user()->can('view', $user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Not authorized to view this user'
                ], 403);
            }
            
            // Return successful response with user
            return response()->json([
                'success' => true,
                'data' => $user
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching the user.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created user in storage.
     * Accessible by: admin only
     *
     * @param Request $request The HTTP request with user data
     * @return \Illuminate\Http\JsonResponse Response with created user or error
     */
    public function store(Request $request)
    {
        try {
            // Validate the request data
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8',
                'role' => 'required|in:admin,manager,staff',
                'status' => 'boolean',
            ]);
            
            // Return validation errors if validation fails
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation Error',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Get the current user
            $currentUser = $request->user();
            
            // Create the user through the service (handles authorization)
            $user = $this->userService->createUser($currentUser, $validator->validated());
            
            // Return successful response with the created user
            return response()->json([
                'success' => true,
                'message' => 'User created successfully',
                'data' => $user
            ], 201);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 403);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating the user.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified user in storage.
     * Accessible by: admin only
     *
     * @param Request $request The HTTP request with updated user data
     * @param string $id The ID of the user to update
     * @return \Illuminate\Http\JsonResponse Response with updated user or error
     */
    public function update(Request $request, string $id)
    {
        try {
            // Find the user
            $user = User::find($id);
            
            // Check if user exists
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }
            
            // Validate the request data
            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|string|email|max:255|unique:users,email,' . $id,
                'password' => 'sometimes|string|min:8',
                'role' => 'sometimes|in:admin,manager,staff',
                'status' => 'sometimes|boolean',
            ]);
            
            // Return validation errors if validation fails
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation Error',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Get the current user
            $currentUser = $request->user();
            
            // Update the user through the service (handles authorization)
            $updatedUser = $this->userService->updateUser($currentUser, $user, $validator->validated());
            
            // Return successful response with updated user
            return response()->json([
                'success' => true,
                'message' => 'User updated successfully',
                'data' => $updatedUser
            ]);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 403);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the user.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified user from storage.
     * Accessible by: admin only
     *
     * @param Request $request The HTTP request
     * @param string $id The ID of the user to delete
     * @return \Illuminate\Http\JsonResponse Response with success message or error
     */
    public function destroy(Request $request, string $id)
    {
        try {
            // Find the user
            $user = User::find($id);
            
            // Check if user exists
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }
            
            // Get the current user
            $currentUser = $request->user();
            
            // Delete the user through the service (handles authorization)
            $this->userService->deleteUser($currentUser, $user);
            
            // Return successful response
            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully'
            ]);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 403);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting the user.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

// app/http/Controllers/userController.php