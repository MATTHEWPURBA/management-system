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