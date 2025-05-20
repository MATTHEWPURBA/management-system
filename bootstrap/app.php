<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\CheckUserStatus;



return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',  // Add this line if missing
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Register middleware aliases
        $middleware->alias([
            'check.userstatus' => \App\Http\Middleware\CheckUserStatus::class,
            // Add any other middleware aliases you need here
        ]);

        
        
        // You can keep any other middleware configurations you need
    })
    ->withProviders([
        // Other providers...
        Laravel\Sanctum\SanctumServiceProvider::class,
    ])


    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();


    // Bootstrap/app.php