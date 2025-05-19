<?php

namespace App\Http\Middleware;

use App\Models\ActivityLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * LogRequest Middleware
 * 
 * This middleware implements a comprehensive API activity logging mechanism that:
 * 1. Records all API requests to the system log for technical monitoring
 * 2. Creates structured activity logs for significant operations (create/update/delete)
 * 3. Provides a full audit trail for security and compliance purposes
 * 
 * It runs after the auth:sanctum middleware to ensure user identification when available.
 */
class LogRequest
{
    /**
     * List of sensitive request parameters that should be redacted from logs
     * This prevents accidental exposure of confidential data in log files
     *
     * @var array<string>
     */
    protected $sensitiveParameters = [
        'password',
        'password_confirmation',
        'current_password',
        'new_password',
        'token',
        'api_key',
        'secret',
    ];

    /**
     * Handle an incoming request.
     *
     * This method processes each incoming API request and:
     * 1. Logs the basic request information to the application log file
     * 2. For write operations (POST/PUT/DELETE), creates an ActivityLog entry
     * 3. Performs redaction of sensitive data before logging
     * 
     * The logging provides two distinct layers of visibility:
     * - Technical logs: For system monitoring and troubleshooting (logs/api_activity.log)
     * - Business logs: For audit trail and user activity tracking (activity_logs table)
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Process the request through the rest of the middleware stack and get the response
        $response = $next($request);
        
        // Get information about the current request
        $method = $request->method();
        $path = $request->path();
        $ip = $request->ip();
        $userAgent = $request->header('User-Agent');
        
        // Redact sensitive parameters from request data before logging
        $requestData = $this->redactSensitiveData($request->all());
        
        // Create the log message with request details
        $logMessage = [
            'method' => $method,
            'path' => $path,
            'ip' => $ip,
            'user_agent' => $userAgent,
            'request_data' => $requestData,
            'status_code' => $response->getStatusCode(),
        ];
        
        // Add authenticated user info if available
        if ($request->user()) {
            $logMessage['user_id'] = $request->user()->id;
            $logMessage['user_email'] = $request->user()->email;
            $logMessage['user_role'] = $request->user()->role;
        } else {
            $logMessage['user'] = 'unauthenticated';
        }
        
        // Log the API request to a dedicated channel
        Log::channel('api_activity')->info('API Request', $logMessage);
        
        // For write operations, also create an ActivityLog entry in the database
        // This implements the business requirement for user action tracking
        if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE']) && $request->user()) {
            // Determine the action type based on HTTP method and URL
            $action = $this->determineActionType($method, $path);
            
            // Create a descriptive message based on the action
            $description = $this->createActionDescription($action, $path, $requestData);
            
            // Create the activity log entry
            ActivityLog::logUserAction(
                $request->user()->id,
                $action,
                $description
            );
        }
        
        return $response;
    }

    /**
     * Redact sensitive data from the request parameters
     * 
     * This method removes or masks sensitive information before logging to prevent
     * accidental exposure of confidential data (passwords, tokens, etc.) in logs.
     * It recursively processes nested arrays to ensure thorough redaction.
     *
     * @param array $data The original request data
     * @return array The sanitized request data safe for logging
     */
    protected function redactSensitiveData(array $data): array
    {
        foreach ($data as $key => $value) {
            // Recursively process nested arrays
            if (is_array($value)) {
                $data[$key] = $this->redactSensitiveData($value);
                continue;
            }
            
            // Redact sensitive fields by replacing their values with [REDACTED]
            if (in_array(Str::lower($key), $this->sensitiveParameters)) {
                $data[$key] = '[REDACTED]';
            }
        }
        
        return $data;
    }

    /**
     * Determine the action type based on HTTP method and path
     * 
     * This method analyzes the request to classify it into specific action categories
     * for more meaningful activity logging. It maps technical HTTP operations to
     * business-relevant action types.
     *
     * @param string $method The HTTP method (POST, PUT, DELETE, etc.)
     * @param string $path The request URI path
     * @return string The classified action type
     */
    protected function determineActionType(string $method, string $path): string
    {
        // Extract the resource type from the path (e.g., "users" from "/api/users")
        $pathParts = explode('/', $path);
        $resource = count($pathParts) > 1 ? $pathParts[count($pathParts) - 2] : $pathParts[0];
        $resource = rtrim($resource, 's'); // Convert plural to singular (users -> user)
        
        // Map HTTP methods to action verbs based on REST conventions
        switch ($method) {
            case 'POST':
                $action = 'create';
                break;
            case 'PUT':
            case 'PATCH':
                $action = 'update';
                break;
            case 'DELETE':
                $action = 'delete';
                break;
            default:
                $action = 'other';
        }
        
        // Special cases for specific endpoints
        if ($path === 'api/login') {
            return 'user_login';
        } else if ($path === 'api/logout') {
            return 'user_logout';
        }
        
        // Combine action verb with resource type (e.g., "create_user", "update_task")
        return $action . '_' . $resource;
    }

    /**
     * Create a human-readable description of the action
     * 
     * This method generates a descriptive message for the activity log based on
     * the action type, path, and relevant request data. The descriptions are designed
     * to be clear and useful for audit review.
     *
     * @param string $action The classified action type
     * @param string $path The request URI path
     * @param array $requestData The sanitized request data
     * @return string A human-readable description
     */
    protected function createActionDescription(string $action, string $path, array $requestData): string
    {
        // Extract resource ID from path if available (for update/delete operations)
        $resourceId = null;
        $pathParts = explode('/', $path);
        if (count($pathParts) > 0 && is_numeric(end($pathParts))) {
            $resourceId = end($pathParts);
        }
        
        // Generate appropriate description based on action type
        switch ($action) {
            case 'create_user':
                $name = $requestData['name'] ?? 'Unknown';
                $role = $requestData['role'] ?? 'Unknown role';
                return "Created new user: {$name} with {$role} role";
                
            case 'update_user':
                return "Updated user information" . ($resourceId ? " for user #{$resourceId}" : "");
                
            case 'delete_user':
                return "Deleted user" . ($resourceId ? " #{$resourceId}" : "");
                
            case 'create_task':
                $title = $requestData['title'] ?? 'Unknown';
                $assignedTo = $requestData['assigned_to'] ?? 'Unassigned';
                return "Created new task: {$title}" . ($assignedTo ? " assigned to user #{$assignedTo}" : "");
                
            case 'update_task':
                $status = isset($requestData['status']) ? " (Status: {$requestData['status']})" : "";
                return "Updated task" . ($resourceId ? " #{$resourceId}" : "") . $status;
                
            case 'delete_task':
                return "Deleted task" . ($resourceId ? " #{$resourceId}" : "");
                
            default:
                return "Performed {$action} operation on {$path}";
        }
    }
}

// app/Http/middleware/LogRequest.php