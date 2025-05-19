<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserStatusTest extends TestCase
{
    use RefreshDatabase;
    
    /**
     * Test that inactive users cannot authenticate.
     */
    public function test_inactive_users_cannot_login(): void
    {
        // Create an inactive user
        $user = User::factory()->create([
            'status' => false,
        ]);
        
        // Attempt to login
        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password', // Factory default password
        ]);
        
        // Assert login was rejected due to inactive status
        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'message' => 'User account is inactive'
        ]);
    }
    
    /**
     * Test that active users can authenticate.
     */
    public function test_active_users_can_login(): void
    {
        // Create an active user
        $user = User::factory()->create([
            'status' => true,
        ]);
        
        // Attempt to login
        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password', // Factory default password
        ]);
        
        // Assert login was successful
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Login successful'
        ]);
        
        // Check that the response contains a token
        $response->assertJsonStructure([
            'data' => [
                'access_token',
                'token_type',
                'user' => [
                    'id',
                    'name',
                    'email',
                    'role',
                ],
            ],
        ]);
    }
    
    /**
     * Test that an inactive user with a valid token cannot access protected routes.
     */
    public function test_inactive_users_with_token_cannot_access_protected_routes(): void
    {
        // Create an active user and get token
        $user = User::factory()->create([
            'status' => true,
        ]);
        
        $loginResponse = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);
        
        $token = $loginResponse->json('data.access_token');
        
        // Now deactivate the user
        $user->status = false;
        $user->save();
        
        // Attempt to access a protected route with the token
        $response = $this->withHeader('Authorization', "Bearer {$token}")
                         ->getJson('/api/tasks');
        
        // Should be rejected due to inactive status
        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'message' => 'Your account is inactive. Please contact an administrator.'
        ]);
    }
}