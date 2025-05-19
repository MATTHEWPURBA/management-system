<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates the tasks table with the required fields:
     * - id: UUID primary key
     * - title: Task title (required)
     * - description: Task description (can be longer text)
     * - assigned_to: UUID of the user this task is assigned to (foreign key to users table)
     * - status: Current status of the task (enum: pending, in_progress, done)
     * - due_date: Date when the task is due
     * - created_by: UUID of the user who created the task (foreign key to users table)
     * - created_at, updated_at: Standard Laravel timestamps
     */
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            // Using UUID as primary key for globally unique identifiers
            // UUIDs prevent sequential enumeration attacks and allow for distributed ID generation
            $table->uuid('id')->primary();
            
            // Task title - required field, contains short summary of task
            $table->string('title');
            
            // Description - using text type to allow for longer, multi-line descriptions
            // Text type can store up to 65,535 characters vs. string's typical 255 limit
            $table->text('description');
            
            // Foreign key reference to the user assigned to this task
            // Using UUID format to match with users.id column data type
            $table->uuid('assigned_to');
            
            // Status tracking with predefined states
            // Using enum type ensures data integrity by limiting to valid states
            $table->enum('status', ['pending', 'in_progress', 'done'])->default('pending');
            
            // Due date tracking - allows for overdue task identification
            $table->date('due_date');
            
            // Foreign key reference to the user who created this task
            // Important for permission checking (users can only modify their own tasks unless admin)
            $table->uuid('created_by');
            
            // Standard Laravel timestamps for creation and modification tracking
            $table->timestamps();
            
            // Foreign key constraints for data integrity
            // On delete cascade ensures that if a user is deleted, their tasks are also removed
            $table->foreign('assigned_to')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};