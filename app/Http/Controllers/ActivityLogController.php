<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogService;
use Illuminate\Http\Request;

/**
 * ActivityLogController - Handles API endpoints for Activity Logs
 * 
 * This controller provides the following endpoints:
 * - GET /api/logs - List activity logs (admin only)
 * - GET /api/logs/{id} - View specific log entry (admin only)
 * 
 * It delegates business logic to the ActivityLogService and focuses on request
 * handling and response formatting.
 */
class ActivityLogController extends Controller
{
    /**
     * The activity log service instance.
     *
     * @var ActivityLogService
     */
    protected $logService;

    /**
     * Create a new controller instance.
     *
     * @param ActivityLogService $logService Injected service for business logic
     */
    public function __construct(ActivityLogService $logService)
    {
        $this->logService = $logService;
    }

    /**
     * Display a listing of activity logs.
     * Accessible by: admin only
     *
     * @param Request $request The HTTP request
     * @return \Illuminate\Http\JsonResponse Response with logs or error
     */
    public function index(Request $request)
    {
        try {
            // Extract filters from the request
            $filters = [];
            
            // Date range filters
            if ($request->has('from_date')) {
                $filters['from_date'] = $request->from_date;
            }
            
            if ($request->has('to_date')) {
                $filters['to_date'] = $request->to_date;
            }
            
            // Action type filter
            if ($request->has('action')) {
                $filters['action'] = $request->action;
            }
            
            // User filter
            if ($request->has('user_id')) {
                $filters['user_id'] = $request->user_id;
            }
            
            // Get the current user
            $currentUser = $request->user();
            
            // Get logs through the service (handles authorization)
            $logs = $this->logService->getLogs($currentUser, $filters);
            
            // Return successful response with logs
            return response()->json([
                'success' => true,
                'data' => $logs
            ]);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 403);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching activity logs.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified activity log.
     * Accessible by: admin only
     *
     * @param Request $request The HTTP request
     * @param string $id The ID of the log to show
     * @return \Illuminate\Http\JsonResponse Response with log or error
     */
    public function show(Request $request, string $id)
    {
        try {
            // Get the current user
            $currentUser = $request->user();
            
            // Get the log through the service (handles authorization)
            $log = $this->logService->getLog($currentUser, $id);
            
            // Check if log was found
            if (!$log) {
                return response()->json([
                    'success' => false,
                    'message' => 'Activity log not found'
                ], 404);
            }
            
            // Return successful response with log
            return response()->json([
                'success' => true,
                'data' => $log
            ]);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 403);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching the activity log.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

// app/http/Controllers/ActivityLogController.php