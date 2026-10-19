<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Omar',
            'email' => 'omar@test.com',
            'password' => '12345678',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('users', [
            'email' => 'omar@test.com',
        ]);
    }

    public function test_user_can_login(): void
    {
        // 1. Create a test user
        $user = User::factory()->create([
            'name' => 'Omar',
            'email' => 'omar@test.com',
            'password' => '12345678',
        ]);

        // 2. Send login request
        $response = $this->postJson('/api/login', [
            'email' => 'omar@test.com',
            'password' => '12345678',
        ]);

        // 3. Verify the response
        $response->assertStatus(200)
            ->assertJsonStructure([
                'token',
                'token_type',
                'user' => [
                    'id',
                    'name',
                    'email',
                ],
            ]);

        // 4. Extract the generated token
        $token = $response->json('token');

        // 5. Verify the token works
        $this->withToken($token)
            ->getJson('/api/user')
            ->assertStatus(200)
            ->assertJsonPath('email', 'omar@test.com');
    }

    public function test_user_cannot_login_with_wrong_password(): void
    {
        // Create a user with the correct password
        User::factory()->create([
            'email' => 'omar@test.com',
            'password' => '12345678',
        ]);

        // Try logging in with the WRONG password
        $response = $this->postJson('/api/login', [
            'email' => 'omar@test.com',
            'password' => 'wrongpassword',
        ]);

        // Check the HTTP response
        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Invalid credentials',
            ]);

        // Check that no token was returned
        $this->assertNull($response->json('token'));

        // Check that no Sanctum token was created
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_guest_cannot_access_protected_routes(): void
    {
        $this->getJson('/api/user')
            ->assertStatus(401);

        $this->getJson('/api/tasks')
            ->assertStatus(401);

        $this->getJson('/api/categories')
            ->assertStatus(401);
    }

    public function test_logout_invalidates_token(): void
    {
        User::factory()->create([
            'email' => 'omar@test.com',
            'password' => '12345678',
        ]);

        $loginResponse = $this->postJson('/api/login', [
            'email' => 'omar@test.com',
            'password' => '12345678',
        ]);

        $loginResponse->assertStatus(200);

        $token = $loginResponse->json('token');

        // Verify token works before logout
        $this->withToken($token)
            ->getJson('/api/user')
            ->assertStatus(200);

        // Logout
        $this->withToken($token)
            ->postJson('/api/logout')
            ->assertStatus(200);

        // Verify token is deleted
        $this->assertDatabaseCount('personal_access_tokens', 0);

        // Clear cached authentication guards
        Auth::forgetGuards();

        // The deleted token should no longer work
        $this->withToken($token)
            ->getJson('/api/user')
            ->assertStatus(401);
    }
}
