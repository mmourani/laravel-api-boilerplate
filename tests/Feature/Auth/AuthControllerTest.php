<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_validates_required_fields()
    {
        $response = $this->postJson('/api/register', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_register_validates_email_format()
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'invalid-email',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_register_validates_password_confirmation()
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password',
            'password_confirmation' => 'different-password',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_register_validates_unique_email()
    {
        $email = fake()->unique()->safeEmail();
        User::factory()->create(['email' => $email]);

        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => $email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_login_validates_required_fields()
    {
        $response = $this->postJson('/api/login', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_login_validates_email_format()
    {
        $response = $this->postJson('/api/login', [
            'email' => 'invalid-email',
            'password' => 'password',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_login_with_non_existent_user()
    {
        $response = $this->postJson('/api/login', [
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password',
        ]);

        $response->assertStatus(401)
            ->assertJson(['message' => 'Invalid credentials']);
    }

    public function test_login_with_wrong_password()
    {
        $email = fake()->unique()->safeEmail();
        User::factory()->create([
            'email' => $email,
            'password' => bcrypt('correct-password'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => $email,
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(401)
            ->assertJson(['message' => 'Invalid credentials']);
    }

    public function test_user_endpoint_requires_authentication()
    {
        $response = $this->getJson('/api/user');
        $response->assertStatus(401);
    }

    public function test_user_endpoint_returns_correct_user()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/user');

        $response->assertOk()
            ->assertJson([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]);
    }

    public function test_logout_requires_authentication()
    {
        $response = $this->postJson('/api/logout');
        $response->assertStatus(401);
    }

    public function test_successful_logout_deletes_current_token()
    {
        $user = \App\Models\User::factory()->create();

        $token = $user->createToken('TestToken')->plainTextToken;

        $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->postJson('/api/logout')
            ->assertOk()
            ->assertJson(['message' => 'Logged out']);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
        ]);
    }

    public function test_successful_registration_returns_correct_structure()
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['token'])
            ->assertJsonPath('token', fn ($token) => is_string($token) && strlen($token) > 0);

        $this->assertNotEmpty($response->json('token'));
    }

    public function test_successful_login_returns_correct_structure()
    {
        $email = fake()->unique()->safeEmail();

        $user = User::factory()->create([
            'email' => $email,
            'password' => bcrypt('password'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => $email,
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['token'])
            ->assertJsonPath('token', fn ($token) => is_string($token) && strlen($token) > 0);

        $this->assertNotEmpty($response->json('token'));
    }
}
