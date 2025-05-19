<?php

namespace Tests\Unit;

use App\Models\Task;
use App\Models\User;
use App\Services\TaskService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskServiceTest extends TestCase
{
    use RefreshDatabase;
    
    protected TaskService $taskService;
    protected User $adminUser;
    protected User $managerUser;
    protected User $staffUser;
    
    public function setUp(): void
    {
        parent::setUp();
        
        // Create the service
        $this->taskService = new TaskService();
        
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
    }
    
    /**
     * Test that tasks are filtered correctly based on user role.
     */
    public function test_tasks_are_filtered_by_role(): void
    {
        // Create some test tasks
        $adminTask = Task::factory()->create([
            'created_by' => $this->adminUser->id,
            'assigned_to' => $this->managerUser->id,
        ]);
        
        $managerTask = Task::factory()->create([
            'created_by' => $this->managerUser->id,
            'assigned_to' => $this->staffUser->id,
        ]);
        
        $staffTask = Task::factory()->create([
            'created_by' => $this->managerUser->id,
            'assigned_to' => $this->staffUser->id,
        ]);
        
        // Test admin can see all tasks
        $adminTasks = $this->taskService->getTasks($this->adminUser);
        $this->assertCount(3, $adminTasks);
        
        // Test manager can see tasks they created and tasks assigned to staff
        $managerTasks = $this->taskService->getTasks($this->managerUser);
        $this->assertCount(2, $managerTasks);
        
        // Test staff can only see tasks assigned to them
        $staffTasks = $this->taskService->getTasks($this->staffUser);
        $this->assertCount(2, $staffTasks);
    }
    
    /**
     * Test role-based task assignment restrictions.
     */
    public function test_role_based_assignment_restrictions(): void
    {
        // Admin can assign tasks to anyone
        $adminAssignToManager = [
            'title' => 'Admin Task 1',
            'description' => 'Test task',
            'status' => 'pending',
            'due_date' => now()->addDays(1)->format('Y-m-d'),
            'assigned_to' => $this->managerUser->id,
        ];
        
        $task1 = $this->taskService->createTask($this->adminUser, $adminAssignToManager);
        $this->assertEquals($this->managerUser->id, $task1->assigned_to);
        
        // Manager can only assign tasks to staff
        $managerAssignToStaff = [
            'title' => 'Manager Task 1',
            'description' => 'Test task',
            'status' => 'pending',
            'due_date' => now()->addDays(1)->format('Y-m-d'),
            'assigned_to' => $this->staffUser->id,
        ];
        
        $task2 = $this->taskService->createTask($this->managerUser, $managerAssignToStaff);
        $this->assertEquals($this->staffUser->id, $task2->assigned_to);
        
        // Staff can only assign tasks to themselves
        $staffAssignToSelf = [
            'title' => 'Staff Task 1',
            'description' => 'Test task',
            'status' => 'pending',
            'due_date' => now()->addDays(1)->format('Y-m-d'),
            'assigned_to' => $this->staffUser->id,
        ];
        
        $task3 = $this->taskService->createTask($this->staffUser, $staffAssignToSelf);
        $this->assertEquals($this->staffUser->id, $task3->assigned_to);
        
        // Test that manager cannot assign to admin (should throw exception)
        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);
        
        $managerAssignToAdmin = [
            'title' => 'Manager Task 2',
            'description' => 'Test task',
            'status' => 'pending',
            'due_date' => now()->addDays(1)->format('Y-m-d'),
            'assigned_to' => $this->adminUser->id,
        ];
        
        $this->taskService->createTask($this->managerUser, $managerAssignToAdmin);
    }
    
    /**
     * Test task overdue detection.
     */
    public function test_overdue_task_detection(): void
    {
        // Create an overdue task
        $overdueTask = Task::factory()->create([
            'due_date' => now()->subDays(2),
            'status' => 'pending',
        ]);
        
        // Create a future task
        $futureTask = Task::factory()->create([
            'due_date' => now()->addDays(2),
            'status' => 'pending',
        ]);
        
        // Create a completed task with past due date (not overdue)
        $completedTask = Task::factory()->create([
            'due_date' => now()->subDays(1),
            'status' => 'done',
        ]);
        
        // Test isOverdue() method
        $this->assertTrue($overdueTask->isOverdue());
        $this->assertFalse($futureTask->isOverdue());
        $this->assertTrue($completedTask->isOverdue()); // Past due date, but status is 'done'
        
        // Test overdue scope
        $overdueTasks = Task::overdue()->get();
        $this->assertCount(1, $overdueTasks);
        $this->assertEquals($overdueTask->id, $overdueTasks->first()->id);
    }
}