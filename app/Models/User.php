<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * User Model - represents a user in the system with role-based access control
 * 
 * This model extends Laravel's Authenticatable class to inherit authentication capabilities
 * and implements HasApiTokens for Sanctum API authentication support.
 * 
 * @property string $id - UUID primary key
 * @property string $name - User's full name
 * @property string $email - User's email address (unique)
 * @property string $password - Bcrypt hashed password
 * @property string $role - Role of the user: 'admin', 'manager', or 'staff'
 * @property bool $status - Active status of the user (true = active, false = inactive)
 * @property \Illuminate\Support\Carbon|null $email_verified_at - When the email was verified
 * @property string|null $remember_token - Token for "remember me" functionality
 * @property \Illuminate\Support\Carbon $created_at - When the user was created
 * @property \Illuminate\Support\Carbon $updated_at - When the user was last updated
 */
class User extends Authenticatable
{
    // Laravel traits that extend the model's functionality
    use HasApiTokens;     // Provides API token authentication via Laravel Sanctum
    use HasFactory;       // Enables model factory usage for testing
    use HasUuids;         // Automatically generates UUIDs for new records
    use Notifiable;       // Enables notifications (email, database, etc.)
    
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
     * Only these fields can be filled using User::create() or $user->fill()
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     * These fields will be excluded when the model is converted to array or JSON.
     * Critical for security to prevent sensitive data exposure.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     * Defines automatic type conversion when attributes are accessed.
     * Improves type safety and ensures consistent data handling.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',  // Automatically hashes password values when set
        'status' => 'boolean',   // Converts 0/1 from database to true/false in PHP
    ];

    /**
     * Defines relationship: A User can have many assigned Tasks
     * 
     * This method establishes a one-to-many relationship between User and Task models.
     * Returns a collection of Task instances that are assigned to this user.
     * Used for querying: $user->assignedTasks()->where('status', 'pending')->get()
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function assignedTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to', 'id');
    }

    /**
     * Defines relationship: A User can have created many Tasks
     * 
     * Establishes a one-to-many relationship between User and Task for tasks created by this user.
     * Different from assignedTasks() - this tracks ownership/creation of tasks.
     * Essential for permission checks (users can manage their own tasks).
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function createdTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'created_by', 'id');
    }

    /**
     * Defines relationship: A User has many ActivityLog entries
     * 
     * Tracks all system actions performed by this user for auditing purposes.
     * Critical for security monitoring and compliance requirements.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class, 'user_id', 'id');
    }

    /**
     * Check if the user is an admin
     * 
     * Convenience method that encapsulates the role check logic for cleaner code.
     * Used in policies and middleware for authorization checks.
     *
     * @return bool True if the user has admin role, false otherwise
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if the user is a manager
     * 
     * Convenience method for role checking in authorization contexts.
     * More readable than comparing strings directly in business logic.
     *
     * @return bool True if the user has manager role, false otherwise
     */
    public function isManager(): bool
    {
        return $this->role === 'manager';
    }

    /**
     * Check if the user is a staff member
     * 
     * Convenience method for role checking in authorization contexts.
     *
     * @return bool True if the user has staff role, false otherwise
     */
    public function isStaff(): bool
    {
        return $this->role === 'staff';
    }

    /**
     * Check if the user is active
     * 
     * Business rule: Inactive users cannot access the system.
     * Used in authentication middleware to restrict login.
     *
     * @return bool True if the user is active, false otherwise
     */
    public function isActive(): bool
    {
        return $this->status === true;
    }
}

// app/Models/User.php