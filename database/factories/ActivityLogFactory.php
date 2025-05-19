<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ActivityLog>
 */
class ActivityLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Get a random user or null (for system logs)
        $users = User::all()->count() > 0
            ? User::all()
            : User::factory()->count(3)->create();
        
        $user = fake()->boolean(80) // 80% chance of having a user
            ? $users->random()
            : null;
        
        // Generate random action type
        $action = fake()->randomElement([
            'create_user',
            'update_user',
            'delete_user',
            'create_task',
            'update_task',
            'delete_task',
            'user_login',
            'user_logout',
            'task_overdue',
        ]);
        
        // Generate appropriate description based on action
        $description = match ($action) {
            'create_user' => 'Created user: ' . fake()->name(),
            'update_user' => 'Updated user: ' . fake()->name(),
            'delete_user' => 'Deleted user: ' . fake()->name(),
            'create_task' => 'Created task: ' . fake()->sentence(),
            'update_task' => 'Updated task: ' . fake()->sentence(),
            'delete_task' => 'Deleted task: ' . fake()->sentence(),
            'user_login' => 'User ' . fake()->name() . ' logged in',
            'user_logout' => 'User ' . fake()->name() . ' logged out',
            'task_overdue' => 'Task overdue: ' . Str::uuid(),
            default => 'Unknown action',
        };
        
        return [
            'id' => Str::uuid(),
            'user_id' => $user?->id,
            'action' => $action,
            'description' => $description,
            'logged_at' => fake()->dateTimeBetween('-1 month', 'now'),
        ];
    }
    
    /**
     * Indicate that this is a system log (no user).
     */
    public function system(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => null,
        ]);
    }
    
    /**
     * Set the user who performed the action.
     */
    public function by(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }
    
    /**
     * Set the log to a specific action type.
     */
    public function action(string $action): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => $action,
        ]);
    }
}

// database/factories/ActivityLogFactory.php