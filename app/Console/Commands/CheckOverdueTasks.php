<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\Task;
use Illuminate\Console\Command;

/**
 * Check Overdue Tasks Command
 * 
 * This command implements the business requirement to automatically detect and log
 * tasks that have passed their due date but have not been completed. It's designed
 * to be run regularly via the Laravel scheduler to maintain data integrity and
 * ensure timely notifications about overdue tasks.
 */
class CheckOverdueTasks extends Command
{
    /**
     * The name and signature of the command.
     * This defines how the command will be invoked from the command line.
     *
     * @var string
     */
    protected $signature = 'tasks:check-overdue';

    /**
     * The console command description.
     * Provides a human-readable explanation of what the command does.
     *
     * @var string
     */
    protected $description = 'Check for overdue tasks and log them';

    /**
     * Execute the console command.
     * 
     * This method:
     * 1. Queries the database for tasks that are now overdue
     * 2. Logs each overdue task in the activity_logs table
     * 3. Outputs a summary of the operation to the console
     * 
     * It uses the Task::overdue scope, which defines overdue tasks as those where:
     * - The due_date is in the past (before today)
     * - The status is not 'done' (incomplete tasks)
     * 
     * @return int Exit code (0 for success, non-zero for failure)
     */
    public function handle()
    {
        // Query for overdue tasks using the scope defined in the Task model
        // This gets tasks where due_date < today AND status != 'done'
        $overdueTasks = Task::overdue()->get();
        
        // Get the count for the console output
        $count = $overdueTasks->count();
        
        // If there are overdue tasks, log each one
        if ($count > 0) {
            foreach ($overdueTasks as $task) {
                // Create an activity log entry for each overdue task
                // This follows the format specified in the business requirements
                ActivityLog::logSystemAction(
                    'task_overdue',
                    "Task overdue: {$task->id}"
                );
                
                // Output details to console for monitoring
                $this->info("Logged overdue task: {$task->title} (ID: {$task->id})");
            }
            
            // Summary output
            $this->info("Processed {$count} overdue " . ($count === 1 ? 'task' : 'tasks'));
        } else {
            // No overdue tasks found
            $this->info('No overdue tasks found.');
        }
        
        // Return success code
        return 0;
    }
}

// app/console/commands/CheckOverdueTasks.php