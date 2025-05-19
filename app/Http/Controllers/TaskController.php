<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Services\TaskService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * TaskController - Handles API endpoints for Task management
 * 
 * This controller provides the following endpoints:
 * - GET /api/tasks - List tasks (filtered by role permissions)
 * - GET /api/tasks/{id} - View specific task (if permitted)
 * - POST /api/tasks - Create new task (with role-based assignment restrictions)
 * - PUT /api/tasks/{id} - Update task (if permitted)
 * - DELETE /api/tasks/{id} - Delete task (admin or creator only)
 * - GET /api/tasks/export - Export tasks to CSV (admin only)
 * 
 * It delegates business logic to the TaskService and focuses on request
 * handling, validation, and response formatting.
 */
class TaskController extends Controller
{
    /**
     * The task service instance.
     *
     * @var TaskService
     */
    protected $taskService;

    /**
     * Create a new controller instance.
     *
     * @param TaskService $taskService Injected service for business logic
     */
    public function __construct(TaskService $taskService)
    {
        $this->taskService = $taskService;
    }

    /**
     * Display a listing of tasks filtered by user role.
     * Accessible by: all roles, with filtering based on permissions
     *
     * @param Request $request The HTTP request
     * @return \Illuminate\Http\JsonResponse Response with tasks list or error
     */
    public function index(Request $request)
    {
        try {
            // Get the current user
            $currentUser = $request->user();
            
            // Get tasks through the service (applies role-based filtering)
            $tasks = $this->taskService->getTasks($currentUser);
            
            // Return successful response with tasks
            return response()->json([
                'success' => true,
                'data' => $tasks
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching tasks.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified task.
     * Accessible by: all roles, subject to permissions
     *
     * @param Request $request The HTTP request
     * @param string $id The ID of the task to show
     * @return \Illuminate\Http\JsonResponse Response with task or error
     */
    public function show(Request $request, string $id)
    {
        try {
            // Get the current user
            $currentUser = $request->user();
            
            // Get the task through the service (handles authorization)
            $task = $this->taskService->getTask($currentUser, $id);
            
            // Check if task was found and authorized
            if (!$task) {
                return response()->json([
                    'success' => false,
                    'message' => 'Task not found or access denied'
                ], 404);
            }
            
            // Return successful response with task
            return response()->json([
                'success' => true,
                'data' => $task
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching the task.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created task in storage.
     * Accessible by: all roles, with assignment restrictions
     *
     * @param Request $request The HTTP request with task data
     * @return \Illuminate\Http\JsonResponse Response with created task or error
     */
    public function store(Request $request)
    {
        try {
            // Validate the request data
            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'description' => 'required|string',
                'assigned_to' => 'required|string|exists:users,id',
                'status' => 'required|in:pending,in_progress,done',
                'due_date' => 'required|date|after_or_equal:today',
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
            
            // Create the task through the service (handles authorization and business rules)
            $task = $this->taskService->createTask($currentUser, $validator->validated());
            
            // Return successful response with created task
            return response()->json([
                'success' => true,
                'message' => 'Task created successfully',
                'data' => $task
            ], 201);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 403);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating the task.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified task in storage.
     * Accessible by: subject to permissions (admin, creator, or assignee)
     *
     * @param Request $request The HTTP request with updated task data
     * @param string $id The ID of the task to update
     * @return \Illuminate\Http\JsonResponse Response with updated task or error
     */
    public function update(Request $request, string $id)
    {
        try {
            // Validate the request data
            $validator = Validator::make($request->all(), [
                'title' => 'sometimes|string|max:255',
                'description' => 'sometimes|string',
                'assigned_to' => 'sometimes|string|exists:users,id',
                'status' => 'sometimes|in:pending,in_progress,done',
                'due_date' => 'sometimes|date|after_or_equal:today',
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
            
            // Update the task through the service (handles authorization and business rules)
            $task = $this->taskService->updateTask($currentUser, $id, $validator->validated());
            
            // Return successful response with updated task
            return response()->json([
                'success' => true,
                'message' => 'Task updated successfully',
                'data' => $task
            ]);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 403);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the task.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified task from storage.
     * Accessible by: admin or creator only
     *
     * @param Request $request The HTTP request
     * @param string $id The ID of the task to delete
     * @return \Illuminate\Http\JsonResponse Response with success message or error
     */
    public function destroy(Request $request, string $id)
    {
        try {
            // Get the current user
            $currentUser = $request->user();
            
            // Delete the task through the service (handles authorization)
            $this->taskService->deleteTask($currentUser, $id);
            
            // Return successful response
            return response()->json([
                'success' => true,
                'message' => 'Task deleted successfully'
            ]);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 403);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting the task.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export tasks to CSV.
     * Accessible by: admin only
     *
     * @param Request $request The HTTP request
     * @return \Symfony\Component\HttpFoundation\StreamedResponse|Illuminate\Http\JsonResponse
     *         CSV download response or error
     */
    public function export(Request $request)
    {
        try {
            // Get the current user
            $currentUser = $request->user();
            
            // Get the CSV export through the service (handles authorization)
            return $this->taskService->exportTasksCsv($currentUser);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 403);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while exporting tasks.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

// app/Http/Controllers/controllers/taskController.php