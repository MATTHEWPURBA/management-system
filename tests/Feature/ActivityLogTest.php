<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;
    
    protected User $adminUser;
    protected User $managerUser;
    protected User $staffUser;
    
    public function setUp(): void
    {
        parent::setUp();
        
        // Create test users with different roles
        $this->adminUser = User::factory()->create([
            'role' => 'admin',
            'status' => true,
        ]);
        
        $this->managerUser = User::factory()->create([
            'role' => 'manager',
            'status' => true,
        ]);
        
        $this->staffUser = User::factory()->create([
            'role' => 'staff',
            'status' => true,
        ]);
        
        // Create some activity logs
        ActivityLog::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'user_id' => $this->adminUser->id,
            'action' => 'create_user',
            'description' => 'Created user: Test User',
            'logged_at' => now(),
        ]);
        
        ActivityLog::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'user_id' => $this->managerUser->id,
            'action' => 'create_task',
            'description' => 'Created task: Test Task',
            'logged_at' => now(),
        ]);
        
        ActivityLog::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'user_id' => null,
            'action' => 'task_overdue',
            'description' => 'Task overdue: 123',
            'logged_at' => now(),
        ]);
    }
    
    /**
     * Test that only admin can access activity logs.
     */
    public function test_only_admin_can_access_logs(): void
    {
        // Admin can access logs
        $adminResponse = $this->actingAs($this->adminUser)
                              ->getJson('/api/logs');
        
        $adminResponse->assertStatus(200);
        $adminResponse->assertJsonStructure([
            'data' => [
                'data',
                'current_page',
                'total',
            ],
        ]);
        
        // Manager cannot access logs
        $managerResponse = $this->actingAs($this->managerUser)
                                ->getJson('/api/logs');
        
        $managerResponse->assertStatus(403);
        
        // Staff cannot access logs
        $staffResponse = $this->actingAs($this->staffUser)
                              ->getJson('/api/logs');
        
        $staffResponse->assertStatus(403);
    }
    
    /**
     * Test that logs are created for key actions.
     */
    public function test_logs_are_created_for_key_actions(): void
    {
        // Test login creates a log
        $loginResponse = $this->postJson('/api/login', [
            'email' => $this->adminUser->email,
            'password' => 'password',
        ]);
        
        $loginResponse->assertStatus(200);
        
        // Should have created a login log
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->adminUser->id,
            'action' => 'user_login',
        ]);
        
        // Get token for authenticated requests
        $token = $loginResponse->json('data.access_token');
        
        // Test user creation creates a log
        $userData = [
            'name' => 'New Test User',
            'email' => 'newtest@example.com',
            'password' => 'password123',
            'role' => 'staff',
            'status' => true,
        ];
        
        $createUserResponse = $this->withHeader('Authorization', "Bearer {$token}")
                                  ->postJson('/api/users', $userData);
        
        $createUserResponse->assertStatus(201);
        
        // Should have created a create_user log
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->adminUser->id,
            'action' => 'create_user',
            'description' => 'Created user: New Test User with staff role',
        ]);
        
        // Test task creation creates a log
        $taskData = [
            'title' => 'Test Log Task',
            'description' => 'Task for testing logs',
            'status' => 'pending',
            'due_date' => now()->addDays(1)->format('Y-m-d'),
            'assigned_to' => $this->staffUser->id,
        ];
        
        $createTaskResponse = $this->withHeader('Authorization', "Bearer {$token}")
                                  ->postJson('/api/tasks', $taskData);
        
        $createTaskResponse->assertStatus(201);
        
        // Should have created a create_task log
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->adminUser->id,
            'action' => 'create_task',
        ]);
    }
}