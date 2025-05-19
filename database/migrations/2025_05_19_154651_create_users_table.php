<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates the users table with the required fields:
     * - id: UUID primary key (overriding Laravel's default integer ID)
     * - name: User's full name
     * - email: User's email address (must be unique)
     * - password: User's hashed password
     * - role: User's role in the system (admin, manager, staff)
     * - status: User's active status (1 for active, 0 for inactive)
     * - email_verified_at: Timestamp for email verification
     * - remember_token: Token for "remember me" functionality
     * - created_at, updated_at: Standard Laravel timestamps
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            // Using UUID instead of auto-incrementing ID
            $table->uuid('id')->primary();
            
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            
            // Role-based access control with enum
            // Enum ensures that only specified values can be stored
            $table->enum('role', ['admin', 'manager', 'staff'])->default('staff');
            
            // Boolean status: 1 = active, 0 = inactive
            // Only active users can log in to the system
            $table->boolean('status')->default(true);
            
            // Standard Laravel user table fields
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};