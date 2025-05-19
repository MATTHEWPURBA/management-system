<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskManagementTest extends TestCase
{
    use RefreshDatabase;
    
    protected User $adminUser;
    protected User $managerUser;
    protected User $staffUser;
    protected Task $task;
    
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
        
        // Create a test task
        $this->task = Task::factory()->create([
            'created_by' => $this->managerUser->id,
            'assigned_to' => $this->staffUser->id,
        ]);
    }
    
    /**
     * Test task listing with different roles.
     */
    public function test_task_listing_respects_role_permissions(): void
    {
        // Admin should see all tasks
        $adminResponse = $this->actingAs($this->adminUser)
                              ->getJson('/api/tasks');
        
        $adminResponse->assertStatus(200);
        $adminResponse->assertJsonCount(1, 'data');
        
        // Create another task assigned to manager
        $managerTask = Task::factory()->create([
            'created_by' => $this->adminUser->id,
            'assigned_to' => $this->managerUser->id,
        ]);
        
        // Manager should only see tasks they created or assigned to staff
        $managerResponse = $this->actingAs($this->managerUser)
                                ->getJson('/api/tasks');
        
        $managerResponse->assertStatus(200);
        $managerResponse->assertJsonCount(1, 'data');
        
        // Staff should only see tasks assigned to them
        $staffResponse = $this->actingAs($this->staffUser)
                              ->getJson('/api/tasks');
        
        $staffResponse->assertStatus(200);
        $staffResponse->assertJsonCount(1, 'data');
    }
    
    /**
     * Test task creation with different roles.
     */
    public function test_task_creation_with_role_based_assignment(): void
    {
        // Admin can assign task to anyone
        $adminTaskData = [
            'title' => 'Admin Task',
            'description' => 'Created by admin',
            'status' => 'pending',
            'due_date' => now()->addDays(1)->format('Y-m-d'),
            'assigned_to' => $this->managerUser->id,
        ];
        
        $adminResponse = $this->actingAs($this->adminUser)
                              ->postJson('/api/tasks', $adminTaskData);
        
        $adminResponse->assertStatus(201);
        $adminResponse->assertJson([
            'success' => true,
            'data' => [
                'title' => 'Admin Task',
                'assigned_to' => $this->managerUser->id,
            ],
        ]);
        
        // Manager can only assign to staff
        $managerTaskData = [
            'title' => 'Manager Task',
            'description' => 'Created by manager',
            'status' => 'pending',
            'due_date' => now()->addDays(1)->format('Y-m-d'),
            'assigned_to' => $this->staffUser->id,
        ];
        
        $managerResponse = $this->actingAs($this->managerUser)
                                ->postJson('/api/tasks', $managerTaskData);
        
        $managerResponse->assertStatus(201);
        
        // Manager cannot assign to admin (should fail)
        $invalidTaskData = [
            'title' => 'Invalid Task',
            'description' => 'Should fail',
            'status' => 'pending',
            'due_date' => now()->addDays(1)->format('Y-m-d'),
            'assigned_to' => $this->adminUser->id,
        ];
        
        $invalidResponse = $this->actingAs($this->managerUser)
                                ->postJson('/api/tasks', $invalidTaskData);
        
        $invalidResponse->assertStatus(403);
        
        // Staff can only assign to themselves
        $staffTaskData = [
            'title' => 'Staff Task',
            'description' => 'Created by staff',
            'status' => 'pending',
            'due_date' => now()->addDays(1)->format('Y-m-d'),
            'assigned_to' => $this->staffUser->id,
        ];
        
        $staffResponse = $this->actingAs($this->staffUser)
                              ->postJson('/api/tasks', $staffTaskData);
        
        $staffResponse->assertStatus(201);
        
        // Staff cannot assign to others (should fail)
        $invalidStaffTaskData = [
            'title' => 'Invalid Staff Task',
            'description' => 'Should fail',
            'status' => 'pending',
            'due_date' => now()->addDays(1)->format('Y-m-d'),
            'assigned_to' => $this->managerUser->id,
        ];
        
        $invalidStaffResponse = $this->actingAs($this->staffUser)
                                     ->postJson('/api/tasks', $invalidStaffTaskData);
        
        $invalidStaffResponse->assertStatus(403);
    }
    
    /**
     * Test task updates with different roles.
     */
    public function test_task_updates_respect_role_permissions(): void
    {
        // Admin can update any task
        $adminUpdateData = [
            'title' => 'Updated by Admin',
            'status' => 'in_progress',
        ];
        
        $adminResponse = $this->actingAs($this->adminUser)
                              ->putJson("/api/tasks/{$this->task->id}", $adminUpdateData);
        
        $adminResponse->assertStatus(200);
        $adminResponse->assertJson([
            'success' => true,
            'data' => [
                'title' => 'Updated by Admin',
                'status' => 'in_progress',
            ],
        ]);
        
        // Reset task
        $this->task->update(['title' => 'Original Title', 'status' => 'pending']);
        
        // Manager can update tasks they created
        $managerUpdateData = [
            'title' => 'Updated by Manager',
            'status' => 'in_progress',
        ];
        
        $managerResponse = $this->actingAs($this->managerUser)
                                ->putJson("/api/tasks/{$this->task->id}", $managerUpdateData);
        
        $managerResponse->assertStatus(200);
        
        // Reset task and assign to manager (not created by them)
        $anotherTask = Task::factory()->create([
            'created_by' => $this->adminUser->id,
            'assigned_to' => $this->managerUser->id,
        ]);
        
        // Manager can update tasks assigned to them
        $managerAssignedUpdateData = [
            'title' => 'Updated by Manager Assignee',
            'status' => 'in_progress',
        ];
        
        $managerAssignedResponse = $this->actingAs($this->managerUser)
                                       ->putJson("/api/tasks/{$anotherTask->id}", $managerAssignedUpdateData);
        
        $managerAssignedResponse->assertStatus(200);
        
        // Staff can update tasks assigned to them
        $staffUpdateData = [
            'title' => 'Updated by Staff',
            'status' => 'in_progress',
        ];
        
        $staffResponse = $this->actingAs($this->staffUser)
                              ->putJson("/api/tasks/{$this->task->id}", $staffUpdateData);
        
        $staffResponse->assertStatus(200);
        
        // Staff cannot update tasks not assigned to them
        $staffTask = Task::factory()->create([
            'created_by' => $this->adminUser->id,
            'assigned_to' => $this->managerUser->id,
        ]);
        
        $invalidStaffUpdate = $this->actingAs($this->staffUser)
                                   ->putJson("/api/tasks/{$staffTask->id}", $staffUpdateData);
        
        $invalidStaffUpdate->assertStatus(403);
    }
    
    /**
     * Test task deletion with different roles.
     */
    public function test_task_deletion_respects_role_permissions(): void
    {
        // Admin can delete any task
        $adminTask = Task::factory()->create([
            'created_by' => $this->staffUser->id,
            'assigned_to' => $this->managerUser->id,
        ]);
        
        $adminDeleteResponse = $this->actingAs($this->adminUser)
                                    ->deleteJson("/api/tasks/{$adminTask->id}");
        
        $adminDeleteResponse->assertStatus(200);
        
        // Creator can delete their own task
        $managerTask = Task::factory()->create([
            'created_by' => $this->managerUser->id,
            'assigned_to' => $this->staffUser->id,
        ]);
        
        $managerDeleteResponse = $this->actingAs($this->managerUser)
                                      ->deleteJson("/api/tasks/{$managerTask->id}");
        
        $managerDeleteResponse->assertStatus(200);
        
        // Staff cannot delete tasks even if assigned to them
        $staffTask = Task::factory()->create([
            'created_by' => $this->managerUser->id,
            'assigned_to' => $this->staffUser->id,
        ]);
        
        $staffDeleteResponse = $this->actingAs($this->staffUser)
                                    ->deleteJson("/api/tasks/{$staffTask->id}");
        
        $staffDeleteResponse->assertStatus(403);
        
        // Staff can delete tasks they created
        $staffCreatedTask = Task::factory()->create([
            'created_by' => $this->staffUser->id,
            'assigned_to' => $this->staffUser->id,
        ]);
        
        $staffCreatedDeleteResponse = $this->actingAs($this->staffUser)
                                           ->deleteJson("/api/tasks/{$staffCreatedTask->id}");
        
        $staffCreatedDeleteResponse->assertStatus(200);
    }
}