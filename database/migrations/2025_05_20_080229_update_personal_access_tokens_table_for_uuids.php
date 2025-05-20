<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration modifies the personal_access_tokens table to work with UUID-based models.
     * The key changes are:
     * 1. Change the tokenable_id column from bigint to string(36) to store UUID values
     * 2. Modify the primary tokenable index to accommodate the new data type
     * 
     * The combination of these changes allows Laravel Sanctum to work seamlessly with
     * models that use the HasUuids trait for primary keys.
     */
    public function up(): void
    {
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            // First, we need to drop the existing indexes that reference tokenable_id
            // since we can't modify them directly - we must drop and recreate
            $table->dropIndex(['tokenable_type', 'tokenable_id']);
            
            // Change the column type from bigint to string(36) to accommodate UUIDs
            // We use 36 characters because a standard UUID is 36 characters long
            // (32 hex digits + 4 hyphens)
            $table->string('tokenable_id', 36)->change();
            
            // Recreate the composite index with the new column type
            $table->index(['tokenable_type', 'tokenable_id']);
        });
    }

    /**
     * Reverse the migrations.
     * 
     * This down method reverts the changes made in the up method,
     * converting the tokenable_id back to a bigint type for standard
     * auto-incrementing IDs.
     */
    public function down(): void
    {
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            // Drop the modified index
            $table->dropIndex(['tokenable_type', 'tokenable_id']);
            
            // Change the column back to bigint
            $table->unsignedBigInteger('tokenable_id')->change();
            
            // Recreate the original index
            $table->index(['tokenable_type', 'tokenable_id']);
        });
    }
};