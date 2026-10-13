<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'loan.officer@test.com',
            'password' => Hash::make('password'),
            'role' => 'loan_officer',
            'authority_limit' => 0,
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'loan.officer@test.com',
            'password' => 'password',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.email', $user->email)
            ->assertJsonPath('user.role', 'loan_officer');

        $this->assertNotEmpty(
            $response->json('token')
        );
    }

    public function test_login_fails_with_invalid_password(): void
    {
        User::factory()->create([
            'email' => 'loan.officer@test.com',
            'password' => Hash::make('password'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'loan.officer@test.com',
            'password' => 'wrong-password',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_protected_user_endpoint_requires_authentication(): void
    {
        $response = $this->getJson('/api/user');

        $response
            ->assertUnauthorized()
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    public function test_authenticated_user_can_view_current_user(): void
    {
        $user = User::factory()->create([
            'role' => 'loan_officer',
            'authority_limit' => 0,
        ]);

        $token = $user
            ->createToken('test-token')
            ->plainTextToken;

        $response = $this
            ->withHeader(
                'Authorization',
                'Bearer '.$token
            )
            ->getJson('/api/user');

        $response
            ->assertOk()
            ->assertJsonPath('id', $user->id)
            ->assertJsonPath('role', 'loan_officer');
    }

    public function test_user_can_logout_current_token(): void
    {
        $user = User::factory()->create([
            'role' => 'loan_officer',
            'authority_limit' => 0,
        ]);

        $token = $user
            ->createToken('test-token')
            ->plainTextToken;

        $response = $this
            ->withHeader(
                'Authorization',
                'Bearer '.$token
            )
            ->postJson('/api/logout');

        $response
            ->assertOk()
            ->assertJson([
                'message' => 'Logged out successfully.',
            ]);

        $this->assertDatabaseCount(
            'personal_access_tokens',
            0
        );

        // Clear the cached authenticated user/guard between requests.
        $this->app['auth']->forgetGuards();

        $this->withHeader(
            'Authorization',
            'Bearer '.$token
        )
            ->getJson('/api/user')
            ->assertUnauthorized();
    }
}
