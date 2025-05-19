<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * UserPolicy - Enforces role-based permissions for User management
 * 
 * This policy implements the access control matrix for User-related operations
 * based on the role requirements defined in the project specification:
 * - admin: Full control over users
 * - manager: Can view users but not modify them
 * - staff: No access to user management
 * 
 * The policy uses Laravel's authorization system to enforce these rules
 * consistently across all access points in the application.
 */
class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     * 
     * According to requirements, only admin and manager roles can view user lists.
     * Staff members should not have access to user information.
     *
     * @param  \App\Models\User  $user  The user attempting to view users
     * @return bool True if authorized, false if not
     */
    public function viewAny(User $user): bool
    {
        // Only admin and manager can view user lists
        return $user->isAdmin() || $user->isManager();
    }

    /**
     * Determine whether the user can view the model.
     * 
     * Similar to viewAny, but for viewing a specific user's details.
     * Only admin and manager can view user details.
     *
     * @param  \App\Models\User  $user  The user attempting to view
     * @param  \App\Models\User  $model The user being viewed
     * @return bool True if authorized, false if not
     */
    public function view(User $user, User $model): bool
    {
        // Only admin and manager can view user details
        return $user->isAdmin() || $user->isManager();
    }

    /**
     * Determine whether the user can create models.
     * 
     * According to requirements, only admin can create new users.
     * Managers and staff cannot create users.
     *
     * @param  \App\Models\User  $user The user attempting to create a new user
     * @return bool True if authorized, false if not
     */
    public function create(User $user): bool
    {
        // Only admin can create users
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can update the model.
     * 
     * According to requirements, only admin can update user information.
     * This includes modifying user roles, status, etc.
     *
     * @param  \App\Models\User  $user  The user attempting the update
     * @param  \App\Models\User  $model The user being updated
     * @return bool True if authorized, false if not
     */
    public function update(User $user, User $model): bool
    {
        // Only admin can update users
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the model.
     * 
     * According to requirements, only admin can delete users.
     * This is a high-privilege operation with significant impact.
     *
     * @param  \App\Models\User  $user  The user attempting the deletion
     * @param  \App\Models\User  $model The user being deleted
     * @return bool True if authorized, false if not
     */
    public function delete(User $user, User $model): bool
    {
        // Only admin can delete users
        // Also prevent admins from deleting themselves
        return $user->isAdmin() && $user->id !== $model->id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     * 
     * This controls hard deletion capabilities. In this system,
     * only admin can perform permanent deletions.
     *
     * @param  \App\Models\User  $user  The user attempting the force delete
     * @param  \App\Models\User  $model The user being force deleted
     * @return bool True if authorized, false if not
     */
    public function forceDelete(User $user, User $model): bool
    {
        // Only admin can force delete users
        // Also prevent admins from deleting themselves
        return $user->isAdmin() && $user->id !== $model->id;
    }
}

// app/Policies/UserPolicy.php