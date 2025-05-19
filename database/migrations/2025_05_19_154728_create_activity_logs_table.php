<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates the activity_logs table to track system actions:
     * - id: UUID primary key
     * - user_id: UUID of the user who performed the action (foreign key to users table)
     * - action: Type of action performed (create_user, update_task, etc.)
     * - description: Detailed description of the action
     * - logged_at: Timestamp when the action was logged
     * - created_at, updated_at: Standard Laravel timestamps
     */
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            // Using UUID as primary key
            // UUIDs are preferred over auto-incrementing integers in distributed systems
            // as they can be generated without a central authority and avoid potential collisions
            $table->uuid('id')->primary();
            
            // The user who performed the action
            // Using UUID to match users.id format
            // Allows nullability in case the system generates logs automatically
            // or the user has been deleted but we want to keep the action history
            $table->uuid('user_id')->nullable();
            
            // The type of action performed
            // Examples: create_user, update_task, login_attempt, task_overdue
            // String type chosen for flexibility and extensibility
            $table->string('action');
            
            // Detailed description of what happened
            // Text type used to accommodate potentially longer descriptions
            // Examples: "User John Doe created", "Task #123 marked as complete"
            $table->text('description');
            
            // Explicit timestamp field for when the action was logged
            // This allows more flexibility than just using created_at
            // For example, it can be set to a specific time for scheduled events
            $table->timestamp('logged_at');
            
            // Standard Laravel timestamps
            $table->timestamps();
            
            // Foreign key constraint for data integrity
            // onDelete set to null means if a user is deleted, their logs remain but user_id is nullified
            // This preserves the audit trail even if users are removed from the system
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};