<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Services\LoggingService;


/**
 * TaskService - Encapsulates business logic for Task management
 * 
 * This service implements the complex business rules for task creation,
 * assignment, updating, and filtering based on user roles. It centralizes
 * these rules to ensure consistent application across the system.
 */
class TaskService
{
    /**
     * Get tasks filtered based on the user's role
     * 
     * Implements the business rule that:
     * - Admin can see all tasks
     * - Manager can see tasks they created or assigned to any staff
     * - Staff can only see tasks assigned to them
     *
     * @param User $user The authenticated user making the request
     * @return \Illuminate\Database\Eloquent\Collection Collection of tasks
     */
    public function getTasks(User $user)
    {
        // Admin has full visibility of all tasks
        if ($user->isAdmin()) {
            return Task::with(['assignee', 'creator'])->get();
        }
        
        // Manager can see tasks they created or assigned to any staff
        if ($user->isManager()) {
            return Task::where(function ($query) use ($user) {
                // Tasks created by this manager
                $query->where('created_by', $user->id)
                      // OR tasks assigned to staff
                      ->orWhereIn('assigned_to', function ($subquery) {
                          $subquery->select('id')
                                   ->from('users')
                                   ->where('role', 'staff');
                      });
            })
            ->with(['assignee', 'creator'])
            ->get();
        }
        
        // Staff can only see tasks assigned to them
        if ($user->isStaff()) {
            return Task::where('assigned_to', $user->id)
                       ->with(['assignee', 'creator'])
                       ->get();
        }
        
        // Fallback for any other role (should not occur due to middleware)
        return collect();
    }

    /**
     * Get a specific task if the user is authorized to view it
     * 
     * @param User $user The authenticated user making the request
     * @param string $taskId The ID of the task to retrieve
     * @return Task|null The task if found and authorized, null otherwise
     */
    public function getTask(User $user, string $taskId)
    {
        $task = Task::with(['assignee', 'creator'])->find($taskId);
        
        if (!$task) {
            return null;
        }
        
        // Check authorization
        if (Gate::allows('view', $task)) {
            return $task;
        }
        
        return null;
    }

    /**
     * Create a new task with proper assignment validation
     * 
     * Implements the business rules:
     * - Admin can assign tasks to anyone
     * - Manager can only assign tasks to staff
     * - Staff can only assign tasks to themselves
     *
     * @param User $currentUser The user creating the task
     * @param array $taskData The validated data for the new task
     * @return Task The newly created task
     */
    public function createTask(User $currentUser, array $taskData)
    {

        $traceId = uniqid('task_service_', true);

        LoggingService::authLog('TaskService::createTask called', [
            'trace_id' => $traceId,
            'user_id' => $currentUser->id,
            'user_role' => $currentUser->role,
        ]);

        // Verify the assigned user exists
        $assignee = User::find($taskData['assigned_to']);
        if (!$assignee) {
            throw new \Exception('The assigned user does not exist.');
        }
        



        
        // // Check if the current user is allowed to assign tasks to this user
        // if (!Gate::allows('assign', [$assignee])) {
        //     throw new \Illuminate\Auth\Access\AuthorizationException(
        //         'You are not authorized to assign tasks to this user.'
        //     );
        // }
        



        LoggingService::authLog('Assignee found', [
            'trace_id' => $traceId,
            'assignee' => [
                'id' => $assignee->id,
                'name' => $assignee->name,
                'role' => $assignee->role
            ]
        ]);
        
        // Start detailed Gate debugging
        LoggingService::authLog('Checking authorization', [
            'trace_id' => $traceId,
            'check_type' => 'Gate::allows',
            'ability' => 'assign',
            'arguments_type' => gettype($assignee),
            'arguments_class' => get_class($assignee),
            'is_array' => is_array($assignee) ? 'true' : 'false',
            'user_role' => $currentUser->role,
            'assignee_role' => $assignee->role
        ]);
        
        // Try both ways of calling Gate::allows for debugging purposes
        $resultDirectParam = Gate::allows('assign', $assignee);
        $resultArrayParam = Gate::allows('assign', [$assignee]); 
        
        LoggingService::authLog('Authorization check results', [
            'trace_id' => $traceId,
            'direct_param_result' => $resultDirectParam ? 'allowed' : 'denied',
            'array_param_result' => $resultArrayParam ? 'allowed' : 'denied'
        ]);
        
        // Use the array parameter approach which is the correct one
        if (!$resultArrayParam) {
            LoggingService::authLog('Authorization denied', [
                'trace_id' => $traceId,
                'user_id' => $currentUser->id,
                'user_role' => $currentUser->role,
                'assignee_id' => $assignee->id,
                'assignee_role' => $assignee->role
            ]);
            
            throw new \Illuminate\Auth\Access\AuthorizationException(
                'You are not authorized to assign tasks to this user.'
            );
        }
        
        LoggingService::authLog('Authorization granted', [
            'trace_id' => $traceId,
            'user_role' => $currentUser->role,
            'assignee_role' => $assignee->role
        ]);



        // Generate a UUID for the new task
        $taskData['id'] = \Illuminate\Support\Str::uuid();
        
        // Set the creator to the current user
        $taskData['created_by'] = $currentUser->id;
        
        // Create the task
        $task = Task::create($taskData);
        
        // Log the action
        ActivityLog::logUserAction(
            $currentUser->id,
            'create_task',
            "Created task: {$task->title} and assigned to user #{$assignee->id}"
        );
        
        return $task;
    }

    /**
     * Update an existing task with proper validation
     * 
     * @param User $currentUser The user updating the task
     * @param string $taskId The ID of the task to update
     * @param array $taskData The validated data for the task update
     * @return Task The updated task
     */
    public function updateTask(User $currentUser, string $taskId, array $taskData)
    {
        // Find the task
        $task = Task::find($taskId);
        if (!$task) {
            throw new \Exception('Task not found.');
        }
        
        // Check update authorization
        if (!Gate::allows('update', $task)) {
            throw new \Illuminate\Auth\Access\AuthorizationException(
                'Not authorized to update this task.'
            );
        }
        
        // If the assigned user is being changed, check assignment permission
        if (isset($taskData['assigned_to']) && $taskData['assigned_to'] !== $task->assigned_to) {
            $assignee = User::find($taskData['assigned_to']);
            if (!$assignee) {
                throw new \Exception('The assigned user does not exist.');
            }
            
            if (!Gate::allows('assign', $assignee)) {
                throw new \Illuminate\Auth\Access\AuthorizationException(
                    'You are not authorized to assign tasks to this user.'
                );
            }
        }
        
        // Track status changes for logging
        $statusChanged = isset($taskData['status']) && $taskData['status'] !== $task->status;
        $oldStatus = $task->status;
        $newStatus = $statusChanged ? $taskData['status'] : null;
        
        // Update the task
        $task->update($taskData);
        
        // Create appropriate log message
        $logMessage = "Updated task: {$task->title}";
        if ($statusChanged) {
            $logMessage .= " (Status changed from {$oldStatus} to {$newStatus})";
        }
        
        // Log the action
        ActivityLog::logUserAction(
            $currentUser->id,
            'update_task',
            $logMessage
        );
        
        return $task;
    }

    /**
     * Delete a task if the user is authorized
     * 
     * @param User $currentUser The user deleting the task
     * @param string $taskId The ID of the task to delete
     * @return bool Success indicator
     */
    public function deleteTask(User $currentUser, string $taskId)
    {
        // Find the task
        $task = Task::find($taskId);
        if (!$task) {
            throw new \Exception('Task not found.');
        }
        
        // Check delete authorization
        if (!Gate::allows('delete', $task)) {
            throw new \Illuminate\Auth\Access\AuthorizationException(
                'Not authorized to delete this task.'
            );
        }
        
        // Store task details for log
        $taskTitle = $task->title;
        
        // Delete the task
        $result = $task->delete();
        
        // Log the action
        ActivityLog::logUserAction(
            $currentUser->id,
            'delete_task',
            "Deleted task: {$taskTitle}"
        );
        
        return $result;
    }

    /**
     * Export tasks to CSV format
     * 
     * @param User $currentUser The user requesting the export
     * @return StreamedResponse A streamable response with CSV data
     */
    public function exportTasksCsv(User $currentUser)
    {
        // Check export authorization (admin only)
        if (!Gate::allows('export', Task::class)) {
            throw new \Illuminate\Auth\Access\AuthorizationException(
                'Not authorized to export tasks.'
            );
        }
        
        // Get tasks based on permissions (same as getTasks method)
        $tasks = $this->getTasks($currentUser);
        
        // Log the export action
        ActivityLog::logUserAction(
            $currentUser->id,
            'export_tasks',
            "Exported tasks to CSV"
        );
        
        // Create a streamed response for the CSV
        $response = new StreamedResponse(function () use ($tasks) {
            $handle = fopen('php://output', 'w');
            
            // Add CSV headers
            fputcsv($handle, [
                'ID',
                'Title',
                'Description',
                'Status',
                'Due Date',
                'Assigned To',
                'Created By',
                'Created At',
                'Updated At'
            ]);
            
            // Add data rows
            foreach ($tasks as $task) {
                fputcsv($handle, [
                    $task->id,
                    $task->title,
                    $task->description,
                    $task->status,
                    $task->due_date->format('Y-m-d'),
                    $task->assignee ? $task->assignee->name : 'Unknown',
                    $task->creator ? $task->creator->name : 'Unknown',
                    $task->created_at->format('Y-m-d H:i:s'),
                    $task->updated_at->format('Y-m-d H:i:s')
                ]);
            }
            
            fclose($handle);
        });
        
        // Set response headers
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="tasks-export-' . date('Y-m-d') . '.csv"');
        
        return $response;
    }
}


// App/Services/TaskService.php