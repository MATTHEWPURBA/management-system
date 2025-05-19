<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Database Seeder for populating the database with initial test data
 * 
 * This seeder creates a set of users with different roles, tasks assigned to them,
 * and activity logs to simulate a working application. It's designed to provide
 * a comprehensive starting point for development and testing.
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with test data.
     * 
     * The seeding process follows this order:
     * 1. Create users with different roles (admin, manager, staff)
     * 2. Create sample tasks assigned to different users
     * 3. Generate activity logs to simulate system usage
     * 
     * This sequential approach ensures referential integrity is maintained
     * throughout the seeding process (e.g., tasks need users to exist first).
     */
    public function run(): void
    {
        // =====================================================================
        // 1. Create Sample Users
        // =====================================================================
        
        // Admin user - has full system access
        $admin = User::create([
            'id' => Str::uuid(),
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'status' => true,
        ]);
        
        // Manager user - can manage staff and their tasks
        $manager = User::create([
            'id' => Str::uuid(),
            'name' => 'Manager User',
            'email' => 'manager@example.com',
            'password' => Hash::make('password'),
            'role' => 'manager',
            'status' => true,
        ]);
        
        // Active staff user
        $staff1 = User::create([
            'id' => Str::uuid(),
            'name' => 'Staff User 1',
            'email' => 'staff1@example.com',
            'password' => Hash::make('password'),
            'role' => 'staff',
            'status' => true,
        ]);
        
        // Another active staff user
        $staff2 = User::create([
            'id' => Str::uuid(),
            'name' => 'Staff User 2',
            'email' => 'staff2@example.com',
            'password' => Hash::make('password'),
            'role' => 'staff',
            'status' => true,
        ]);
        
        // Inactive staff user - demonstrates status-based restrictions
        $inactiveStaff = User::create([
            'id' => Str::uuid(),
            'name' => 'Inactive Staff User',
            'email' => 'inactive@example.com',
            'password' => Hash::make('password'),
            'role' => 'staff',
            'status' => false,
        ]);
        
        // =====================================================================
        // 2. Create Sample Tasks
        // =====================================================================
        
        // Admin's tasks
        $task1 = Task::create([
            'id' => Str::uuid(),
            'title' => 'Review System Architecture',
            'description' => 'Review the current system architecture and identify improvements.',
            'assigned_to' => $manager->id, // Assigned to manager
            'status' => 'pending',
            'due_date' => now()->addWeeks(1),
            'created_by' => $admin->id,
        ]);
        
        $task2 = Task::create([
            'id' => Str::uuid(),
            'title' => 'Implement Authentication System',
            'description' => 'Implement the authentication system using Laravel Sanctum.',
            'assigned_to' => $staff1->id, // Assigned to staff1
            'status' => 'in_progress',
            'due_date' => now()->addDays(5),
            'created_by' => $admin->id,
        ]);
        
        // Manager's tasks
        $task3 = Task::create([
            'id' => Str::uuid(),
            'title' => 'Create Frontend Dashboard',
            'description' => 'Design and implement the frontend dashboard using vanilla JS.',
            'assigned_to' => $staff1->id, // Assigned to staff1
            'status' => 'pending',
            'due_date' => now()->addDays(7),
            'created_by' => $manager->id,
        ]);
        
        $task4 = Task::create([
            'id' => Str::uuid(),
            'title' => 'Implement Task CRUD Operations',
            'description' => 'Implement create, read, update, and delete operations for tasks.',
            'assigned_to' => $staff2->id, // Assigned to staff2
            'status' => 'pending',
            'due_date' => now()->addDays(3),
            'created_by' => $manager->id,
        ]);
        
        // Overdue task for testing the scheduler
        $overdueTask = Task::create([
            'id' => Str::uuid(),
            'title' => 'Overdue Task Example',
            'description' => 'This task is intentionally set as overdue for testing the scheduler.',
            'assigned_to' => $staff2->id, // Assigned to staff2
            'status' => 'pending',
            'due_date' => now()->subDays(2), // 2 days in the past
            'created_by' => $manager->id,
        ]);
        
        // =====================================================================
        // 3. Create Sample Activity Logs
        // =====================================================================
        
        // Log the creation of users
        ActivityLog::create([
            'id' => Str::uuid(),
            'user_id' => $admin->id,
            'action' => 'create_user',
            'description' => "Created user: {$manager->name} with manager role",
            'logged_at' => now()->subMinutes(30),
        ]);
        
        ActivityLog::create([
            'id' => Str::uuid(),
            'user_id' => $admin->id,
            'action' => 'create_user',
            'description' => "Created user: {$staff1->name} with staff role",
            'logged_at' => now()->subMinutes(25),
        ]);
        
        // Log user logins
        ActivityLog::create([
            'id' => Str::uuid(),
            'user_id' => $admin->id,
            'action' => 'user_login',
            'description' => "User {$admin->name} logged in",
            'logged_at' => now()->subMinutes(20),
        ]);
        
        ActivityLog::create([
            'id' => Str::uuid(),
            'user_id' => $manager->id,
            'action' => 'user_login',
            'description' => "User {$manager->name} logged in",
            'logged_at' => now()->subMinutes(15),
        ]);
        
        // Log task creation
        ActivityLog::create([
            'id' => Str::uuid(),
            'user_id' => $admin->id,
            'action' => 'create_task',
            'description' => "Created task: {$task1->title} and assigned to {$manager->name}",
            'logged_at' => now()->subMinutes(10),
        ]);
        
        ActivityLog::create([
            'id' => Str::uuid(),
            'user_id' => $manager->id,
            'action' => 'create_task',
            'description' => "Created task: {$task3->title} and assigned to {$staff1->name}",
            'logged_at' => now()->subMinutes(5),
        ]);
        
        // Log for overdue task
        ActivityLog::create([
            'id' => Str::uuid(),
            'user_id' => null, // Null user for system-generated logs
            'action' => 'task_overdue',
            'description' => "Task overdue: {$overdueTask->id}",
            'logged_at' => now()->subHours(12),
        ]);
    }
}

// database/seeders/DatabaseSeeder.php