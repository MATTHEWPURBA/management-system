<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * UserService - Encapsulates business logic for User management
 * 
 * This service centralizes all user-related operations, ensuring consistent
 * application of business rules, validation, and side effects (like logging).
 * It abstracts these details away from controllers, making them more focused
 * on request handling rather than business logic.
 */
class UserService
{
    /**
     * Get users based on role permissions
     * 
     * Enforces the business rule that:
     * - Admins can see all users
     * - Managers can see all users
     * - Staff have no access to user lists (this should be prevented at the policy level)
     *
     * @param User $currentUser The authenticated user making the request
     * @return \Illuminate\Database\Eloquent\Collection Collection of users
     */
    public function getUsers(User $currentUser)
    {
        // Check authorization first
        if (!Gate::allows('viewAny', User::class)) {
            throw new \Illuminate\Auth\Access\AuthorizationException('Not authorized to view users.');
        }
        
        // Both admin and manager can see all users
        // No special filtering needed as this is enforced at the policy level
        return User::all();
    }

    /**
     * Create a new user with role-based validation
     * 
     * Handles the business logic for user creation, including:
     * - Validating that only admins can create users
     * - Generating a UUID for the new user
     * - Hashing the password securely
     * - Creating appropriate activity logs
     *
     * @param User $currentUser The user creating the new user
     * @param array $userData The validated data for the new user
     * @return User The newly created user
     */
    public function createUser(User $currentUser, array $userData)
    {
        // Check authorization first
        if (!Gate::allows('create', User::class)) {
            throw new \Illuminate\Auth\Access\AuthorizationException('Not authorized to create users.');
        }
        
        // Generate a UUID for the new user
        $userData['id'] = Str::uuid();
        
        // Hash the password
        $userData['password'] = Hash::make($userData['password']);
        
        // Create the user
        $user = User::create($userData);
        
        // Log the action
        ActivityLog::logUserAction(
            $currentUser->id,
            'create_user',
            "Created user: {$user->name} with {$user->role} role"
        );
        
        return $user;
    }

    /**
     * Update an existing user
     * 
     * Handles the business logic for user updates, including:
     * - Enforcing that only admins can update users
     * - Password hashing (if password is being changed)
     * - Creating appropriate activity logs
     *
     * @param User $currentUser The user performing the update
     * @param User $user The user being updated
     * @param array $userData The validated data for the user update
     * @return User The updated user
     */
    public function updateUser(User $currentUser, User $user, array $userData)
    {
        // Check authorization first
        if (!Gate::allows('update', $user)) {
            throw new \Illuminate\Auth\Access\AuthorizationException('Not authorized to update this user.');
        }
        
        // If password is provided, hash it
        if (isset($userData['password'])) {
            $userData['password'] = Hash::make($userData['password']);
        }
        
        // Update the user
        $user->update($userData);
        
        // Log the action
        ActivityLog::logUserAction(
            $currentUser->id,
            'update_user',
            "Updated user: {$user->name}"
        );
        
        return $user;
    }

    /**
     * Delete a user
     * 
     * Handles the business logic for user deletion, including:
     * - Enforcing that only admins can delete users
     * - Creating appropriate activity logs
     * - Preventing admins from deleting themselves
     *
     * @param User $currentUser The user performing the deletion
     * @param User $user The user being deleted
     * @return bool Success indicator
     */
    public function deleteUser(User $currentUser, User $user)
    {
        // Check authorization first
        if (!Gate::allows('delete', $user)) {
            throw new \Illuminate\Auth\Access\AuthorizationException('Not authorized to delete this user.');
        }
        
        // Prevent self-deletion (additional safeguard)
        if ($currentUser->id === $user->id) {
            throw new \Exception('Cannot delete your own account.');
        }
        
        // Store user name for the log
        $userName = $user->name;
        
        // Delete the user
        $result = $user->delete();
        
        // Log the action
        ActivityLog::logUserAction(
            $currentUser->id,
            'delete_user',
            "Deleted user: {$userName}"
        );
        
        return $result;
    }
}


// App/Services/UserService.php