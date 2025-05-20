<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class LoggingService
{
    const AUTH_CHANNEL = 'auth_debug';
    
    /**
     * Log authentication and authorization events with detailed context
     *
     * @param string $message The log message
     * @param array $context Additional context data
     * @return void
     */
    public static function authLog(string $message, array $context = []): void
    {
        // Add timestamp for precise timing analysis
        $context['timestamp'] = microtime(true);
        
        // Add trace ID to correlate related log entries
        if (!isset($context['trace_id'])) {
            $context['trace_id'] = uniqid('auth_', true);
        }
        
        // Add call stack for deeper debugging if needed
        if (!isset($context['stack_trace'])) {
            $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
            $caller = isset($backtrace[1]) ? $backtrace[1] : null;
            
            $context['caller'] = $caller ? 
                ($caller['class'] ?? '') . ($caller['type'] ?? '') . ($caller['function'] ?? '') :
                'unknown';
                
            $context['file'] = $backtrace[0]['file'] ?? 'unknown';
            $context['line'] = $backtrace[0]['line'] ?? 'unknown';
        }
        
        // Write to the auth_debug channel
        Log::channel('daily')->info('[AUTH DEBUG] ' . $message, $context);
    }
}

// app/Services/LoggingService.php