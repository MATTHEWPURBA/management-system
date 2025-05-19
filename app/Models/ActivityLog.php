<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ActivityLog Model - tracks system actions for audit and monitoring
 * 
 * This model implements a comprehensive audit trail system that records all significant 
 * actions taken within the application. It functions as an immutable append-only log
 * that serves both security and compliance purposes.
 * 
 * The implementation follows the Observer pattern in that it passively records actions
 * performed throughout the system without directly participating in business logic.
 * Each log entry is designed to be immutable once created, preserving the integrity
 * of the audit trail.
 * 
 * @property string $id - UUID primary key for globally unique identification across systems
 * @property string|null $user_id - UUID of user who performed the action (nullable for system actions)
 * @property string $action - Type of action performed (create_user, update_task, login_attempt, etc.)
 * @property string $description - Detailed human-readable description of what happened
 * @property \Illuminate\Support\Carbon $logged_at - Precise timestamp when the action occurred
 * @property \Illuminate\Support\Carbon $created_at - When the log entry was created
 * @property \Illuminate\Support\Carbon $updated_at - When the log entry was last updated
 */
class ActivityLog extends Model
{
    // Laravel traits that extend the model's functionality
    use HasFactory;  // Enables model factory pattern for testing
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
     * The table associated with the model.
     * Explicitly defined to ensure consistency even if the model name changes.
     *
     * @var string
     */
    protected $table = 'activity_logs';

    /**
     * The attributes that are mass assignable.
     * This whitelist enhances security by preventing mass-assignment vulnerabilities.
     * Only these fields can be filled using ActivityLog::create() or $log->fill()
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'action',
        'description',
        'logged_at',
    ];

    /**
     * The attributes that should be cast.
     * Defines automatic type conversion when attributes are accessed.
     * Ensures consistent handling of temporal data.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'logged_at' => 'datetime',  // Automatically converts to Carbon instance for date manipulation
    ];

    /**
     * Defines relationship: An ActivityLog belongs to a User (actor)
     * 
     * Establishes the inverse of the one-to-many relationship from User->activityLogs.
     * Returns the User model instance of the person who performed this action.
     * Can be null for system-generated logs or if the user has been deleted.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Creates a new system activity log without user association
     * 
     * This static factory method provides a convenient way to log system actions
     * that aren't initiated by a specific user (e.g., scheduled tasks, system jobs).
     * It automatically sets the logged_at timestamp to the current time.
     *
     * @param string $action The type of action performed
     * @param string $description Detailed description of the action
     * @return ActivityLog The newly created log record
     */
    public static function logSystemAction(string $action, string $description): self
    {
        return self::create([
            'action' => $action,
            'description' => $description,
            'logged_at' => now(),
        ]);
    }

    /**
     * Creates a new user activity log
     * 
     * This static factory method provides a convenient way to log user-initiated actions.
     * It automatically sets the logged_at timestamp to the current time.
     *
     * @param string $userId The UUID of the user who performed the action
     * @param string $action The type of action performed
     * @param string $description Detailed description of the action
     * @return ActivityLog The newly created log record
     */
    public static function logUserAction(string $userId, string $action, string $description): self
    {
        return self::create([
            'user_id' => $userId,
            'action' => $action,
            'description' => $description,
            'logged_at' => now(),
        ]);
    }

    /**
     * Scope a query to only include logs of a specific action type
     * 
     * This Eloquent query scope enhances code readability and reusability.
     * It allows filtering logs by action type in a more expressive syntax.
     * Example usage: ActivityLog::ofType('login_attempt')->get();
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $action The action type to filter by
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfType($query, $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope a query to only include logs from a specific user
     * 
     * This Eloquent query scope simplifies filtering logs by user.
     * Example usage: ActivityLog::fromUser($userId)->get();
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $userId The user ID to filter by
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFromUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to only include logs from a specific time period
     * 
     * This Eloquent query scope enables time-based filtering for analytics and reporting.
     * Example usage: ActivityLog::during('2023-01-01', '2023-01-31')->get();
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string|null $fromDate Start date (inclusive) in Y-m-d format, or null for no start limit
     * @param string|null $toDate End date (inclusive) in Y-m-d format, or null for no end limit
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDuring($query, $fromDate = null, $toDate = null)
    {
        if ($fromDate) {
            $query->where('logged_at', '>=', $fromDate . ' 00:00:00');
        }
        
        if ($toDate) {
            $query->where('logged_at', '<=', $toDate . ' 23:59:59');
        }
        
        return $query;
    }
}


// app/models/ActivityLog.php   