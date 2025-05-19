<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * TaskPolicy - Enforces role-based permissions for Task management
 * 
 * This policy implements the complex access control matrix for Task-related operations
 * based on the role requirements defined in the project specification:
 * - admin: Full control over all tasks
 * - manager: Can manage tasks for their team and assign tasks to staff
 * - staff: Can only manage tasks assigned to themselves
 * 
 * The policy is also responsible for enforcing business rules like:
 * - Managers can only assign tasks to staff (not to admin or other managers)
 * - Users can only see tasks created by them or assigned to them
 */
class TaskPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any tasks.
     * 
     * All roles can view tasks, but the TaskController will filter which
     * tasks are actually returned based on the user's role:
     * - admin: All tasks
     * - manager: Tasks created by them or assigned to their team (staff)
     * - staff: Only tasks assigned to them
     *
     * @param  \App\Models\User  $user  The user attempting to view tasks
     * @return bool True for all authenticated users (filtering happens in controller)
     */
    public function viewAny(User $user): bool
    {
        // All authenticated users can access the task list endpoint
        // The controller is responsible for filtering the tasks based on role
        return true;
    }

    /**
     * Determine whether the user can view a specific task.
     * 
     * This implements the business rule that users can only see tasks
     * that are either:
     * 1. Created by them (task ownership)
     * 2. Assigned to them (task assignment)
     * 3. Or they are an admin (full access)
     *
     * @param  \App\Models\User  $user  The user attempting to view the task
     * @param  \App\Models\Task  $task  The task being viewed
     * @return bool True if authorized, false if not
     */
    public function view(User $user, Task $task): bool
    {
        // Admin has full access to all tasks
        if ($user->isAdmin()) {
            return true;
        }
        
        // Managers can view tasks they created or tasks assigned to any staff
        if ($user->isManager()) {
            // Check if they created the task
            if ($task->created_by === $user->id) {
                return true;
            }
            
            // Or check if the task is assigned to a staff member
            $assignee = User::find($task->assigned_to);
            return $assignee && $assignee->isStaff();
        }
        
        // Staff can only view tasks assigned to them
        if ($user->isStaff()) {
            return $task->assigned_to === $user->id;
        }
        
        // Default deny for any other scenario
        return false;
    }

    /**
     * Determine whether the user can create a new task.
     * 
     * All users can create tasks, but there are assignment restrictions
     * that are enforced in the controller/service layer:
     * - Managers can only assign tasks to staff
     * - Staff can only assign tasks to themselves
     *
     * @param  \App\Models\User  $user The user attempting to create a task
     * @return bool True for all authenticated users (validation happens in controller)
     */
    public function create(User $user): bool
    {
        // All users can create tasks
        // But assignment restrictions are enforced in the controller/service
        return true;
    }

    /**
     * Determine whether the user can update a task.
     * 
     * This implements complex business logic for task updates:
     * - Admins can update any task
     * - Managers can update tasks they created or tasks assigned to staff
     * - Staff can only update tasks assigned to them
     *
     * @param  \App\Models\User  $user  The user attempting the update
     * @param  \App\Models\Task  $task  The task being updated
     * @return bool True if authorized, false if not
     */
    public function update(User $user, Task $task): bool
    {
        // Admin has full update access
        if ($user->isAdmin()) {
            return true;
        }
        
        // Managers can update tasks they created
        if ($user->isManager() && $task->created_by === $user->id) {
            return true;
        }
        
        // Managers can also update tasks assigned to staff
        if ($user->isManager()) {
            $assignee = User::find($task->assigned_to);
            if ($assignee && $assignee->isStaff()) {
                return true;
            }
        }
        
        // Staff can only update tasks assigned to them
        if ($user->isStaff() && $task->assigned_to === $user->id) {
            return true;
        }
        
        // Default deny for any other scenario
        return false;
    }

    /**
     * Determine whether the user can delete a task.
     * 
     * According to requirements:
     * - Admins can delete any task
     * - Users (managers/staff) can only delete tasks they created
     *
     * @param  \App\Models\User  $user  The user attempting the deletion
     * @param  \App\Models\Task  $task  The task being deleted
     * @return bool True if authorized, false if not
     */
    public function delete(User $user, Task $task): bool
    {
        // Admin can delete any task
        if ($user->isAdmin()) {
            return true;
        }
        
        // Other users can only delete tasks they created
        return $task->created_by === $user->id;
    }

    /**
     * Determine whether a user is allowed to assign a task to a specific user.
     * 
     * This enforces the business rule that:
     * - Admin can assign tasks to anyone
     * - Managers can only assign tasks to staff
     * - Staff can only assign tasks to themselves
     *
     * @param  \App\Models\User  $user       The user attempting to assign the task
     * @param  \App\Models\User  $assignee   The user receiving the task assignment
     * @return bool True if the assignment is allowed, false otherwise
     */
    public function assign(User $user, User $assignee): bool
    {
        // Admin can assign tasks to anyone
        if ($user->isAdmin()) {
            return true;
        }
        
        // Managers can only assign tasks to staff
        if ($user->isManager()) {
            return $assignee->isStaff();
        }
        
        // Staff can only assign tasks to themselves
        if ($user->isStaff()) {
            return $user->id === $assignee->id;
        }
        
        // Default deny for any other scenario
        return false;
    }

    /**
     * Determine whether the user can export tasks to CSV.
     * 
     * This is an admin-only operation as it can potentially
     * expose sensitive business data.
     *
     * @param  \App\Models\User  $user The user attempting to export
     * @return bool True if authorized (admin only), false if not
     */
    public function export(User $user): bool
    {
        // Only admin can export tasks
        return $user->isAdmin();
    }
}


// app/policies/taskPolicy.php