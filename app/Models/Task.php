<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Task Model - represents a task that can be assigned to users
 * 
 * This model handles all task-related data and relationships, including assignment
 * tracking, status management, and ownership attribution. Tasks are central to the
 * system's workflow management and permission system.
 * 
 * @property string $id - UUID primary key
 * @property string $title - Task title
 * @property string $description - Detailed task description
 * @property string $assigned_to - UUID of user assigned to this task
 * @property string $status - Current task status: 'pending', 'in_progress', or 'done'
 * @property \Illuminate\Support\Carbon $due_date - When the task is due
 * @property string $created_by - UUID of user who created this task
 * @property \Illuminate\Support\Carbon $created_at - When the task was created
 * @property \Illuminate\Support\Carbon $updated_at - When the task was last updated
 */
class Task extends Model
{
    // Laravel traits that extend the model's functionality
    use HasFactory;  // Enables model factory for testing
    use HasUuids;    // Automatically generates UUIDs for new records
    
    /**
     * Primary key attributes configuration:
     * - Using 'id' string as primary key (UUID) instead of default auto-incrementing integer
     * - Explicitly specifying the key type as string to match UUID format
     * - Disabling auto-incrementing behavior since UUIDs are used
     */
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     * This whitelist enhances security by preventing mass-assignment vulnerabilities.
     * Only these fields can be filled using Task::create() or $task->fill()
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'description',
        'assigned_to',
        'status',
        'due_date',
        'created_by',
    ];

    /**
     * The attributes that should be cast.
     * Defines automatic type conversion when attributes are accessed.
     * Particularly important for date values to ensure proper formatting and comparison.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'due_date' => 'date',  // Automatically converts to Carbon instance for date manipulation
    ];

    /**
     * Defines relationship: A Task belongs to a User (assignee)
     * 
     * This establishes the inverse of the one-to-many relationship from User->assignedTasks.
     * Returns the User model instance of the person assigned to this task.
     * Critical for permission checks and displaying assignee information.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to', 'id');
    }

    /**
     * Defines relationship: A Task belongs to a User (creator)
     * 
     * This establishes the inverse of the one-to-many relationship from User->createdTasks.
     * Returns the User model instance of the person who created this task.
     * Used for ownership validation and audit trails.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    /**
     * Checks if the task is overdue based on the current date
     * 
     * Business logic method that compares the due_date with current date.
     * Used by the overdue checker command and for status displays.
     *
     * @return bool True if the task's due date has passed, false otherwise
     */
    public function isOverdue(): bool
    {
        return $this->due_date < now()->startOfDay();
    }

    /**
     * Scope a query to only include overdue tasks
     * 
     * Eloquent query scope that filters the query to only return overdue tasks.
     * Used in the scheduler command and for dashboard filtering.
     * Example usage: Task::overdue()->get();
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now()->startOfDay())
                     ->where('status', '!=', 'done');
    }

    /**
     * Scope a query to only include tasks with specified status
     * 
     * Eloquent query scope for filtering tasks by their status.
     * Enhances code readability and reusability across the application.
     * Example usage: Task::withStatus('pending')->get();
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $status The status to filter by ('pending', 'in_progress', or 'done')
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }
}


// app/models/Task.php