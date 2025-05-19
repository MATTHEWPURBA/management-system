<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

/**
 * ActivityLogService - Encapsulates business logic for Activity Logs
 * 
 * This service centralizes all operations related to retrieving and
 * filtering activity logs, ensuring that access controls are properly
 * applied according to the role-based permissions.
 */
class ActivityLogService
{
    /**
     * Get activity logs based on user permissions
     * 
     * According to the requirements, only admins can view logs.
     * This method enforces that restriction.
     *
     * @param User $user The authenticated user making the request
     * @param array $filters Optional filters (date range, action type, etc.)
     * @return \Illuminate\Pagination\LengthAwarePaginator Paginated activity logs
     */
    public function getLogs(User $user, array $filters = [])
    {
        // Check authorization first (admin only)
        if (!Gate::allows('view-logs')) {
            throw new \Illuminate\Auth\Access\AuthorizationException('Not authorized to view activity logs.');
        }
        
        // Start with a base query
        $query = ActivityLog::with('user');
        
        // Apply filters if provided
        if (!empty($filters)) {
            // Filter by date range
            if (isset($filters['from_date']) && isset($filters['to_date'])) {
                $query->during($filters['from_date'], $filters['to_date']);
            }
            
            // Filter by action type
            if (isset($filters['action'])) {
                $query->ofType($filters['action']);
            }
            
            // Filter by user
            if (isset($filters['user_id'])) {
                $query->fromUser($filters['user_id']);
            }
        }
        
        // Order by most recent first
        $query->orderBy('logged_at', 'desc');
        
        // Paginate the results (15 logs per page)
        return $query->paginate(15);
    }

    /**
     * Get a specific activity log entry
     * 
     * @param User $user The authenticated user making the request
     * @param string $logId The ID of the log to retrieve
     * @return ActivityLog|null The log if found and authorized, null otherwise
     */
    public function getLog(User $user, string $logId)
    {
        // Check authorization first (admin only)
        if (!Gate::allows('view-logs')) {
            throw new \Illuminate\Auth\Access\AuthorizationException('Not authorized to view activity logs.');
        }
        
        // Return the log with user information
        return ActivityLog::with('user')->find($logId);
    }
}


// App/Services/ActivityLogService.php