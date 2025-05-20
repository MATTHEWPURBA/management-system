<?php

namespace App\Providers;

use App\Services\ActivityLogService;
use App\Services\LoggingService;
use App\Services\TaskService;
use App\Services\UserService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Pagination\Paginator;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register implemented services
        $this->app->singleton(ActivityLogService::class);
        $this->app->singleton(TaskService::class);
        $this->app->singleton(UserService::class);
            // Register the LoggingService as a singleton
        $this->app->singleton(LoggingService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        // Set default string length for MySQL
        Schema::defaultStringLength(191);
        
        // Configure pagination to use Bootstrap
        Paginator::useBootstrap();
    }
}


// App/Providers/AppServiceProvider.php