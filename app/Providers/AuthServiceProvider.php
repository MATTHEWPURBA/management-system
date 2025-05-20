<?php

namespace App\Providers;

use App\Models\ActivityLog;
use App\Models\Task;
use App\Models\User;
use App\Policies\TaskPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        User::class => UserPolicy::class,
        Task::class => TaskPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // Register the policies
        $this->registerPolicies();
        
        // Define gates for activity logs and other permissions
        

            // Add this explicit Gate definition
    Gate::define('assign', function (User $user, User $assignee) {
        // Admin can assign tasks to anyone
        if ($user->isAdmin()) {
            return true;
        }
        
        // Managers can only assign tasks to staff
        if ($user->isManager()) {
            return $assignee->isStaff();
        }
        
        // Staff can only assign tasks to themselves
        if ($user->isStaff()) {
            return $user->id === $assignee->id;
        }
        
        // Default deny
        return false;
    });

        // Only admin can view logs
        Gate::define('view-logs', function (User $user) {
            return $user->isAdmin();
        });
        
        // Only admin can manage users
        Gate::define('manage-users', function (User $user) {
            return $user->isAdmin();
        });
        
        // Only admin can export tasks
        Gate::define('export-tasks', function (User $user) {
            return $user->isAdmin();
        });
    }
}


// App/Providers/AuthServiceProvider.php