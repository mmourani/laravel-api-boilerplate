<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{

    protected string $prefix = '/api/v1';

    public function test_register_validates_required_fields()
    {
        $this->postJson("{$this->prefix}/register", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_register_validates_email_format()
    {
        $this->postJson("{$this->prefix}/register", [
            'name' => 'Test User',
            'email' => 'invalid-email',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['email']);
    }

    public function test_register_validates_password_confirmation()
    {
        $this->postJson("{$this->prefix}/register", [
            'name' => 'Test User',
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password',
            'password_confirmation' => 'not-matching',
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['password']);
    }

    public function test_register_validates_unique_email()
    {
        $email = fake()->unique()->safeEmail();
        User::factory()->create(['email' => $email]);

        $this->postJson("{$this->prefix}/register", [
            'name' => 'Test User',
            'email' => $email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['email']);
    }

    public function test_login_validates_required_fields()
    {
        $this->postJson("{$this->prefix}/login", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_login_validates_email_format()
    {
        $this->postJson("{$this->prefix}/login", [
            'email' => 'invalid-email',
            'password' => 'password',
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['email']);
    }

    public function test_login_with_non_existent_user()
    {
        $this->postJson("{$this->prefix}/login", [
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password',
        ])->assertStatus(401)
          ->assertJson(['message' => 'Invalid credentials']);
    }

    public function test_login_with_wrong_password()
    {
        $email = fake()->unique()->safeEmail();
        User::factory()->create([
            'email' => $email,
            'password' => bcrypt('correct-password'),
        ]);

        $this->postJson("{$this->prefix}/login", [
            'email' => $email,
            'password' => 'wrong-password',
        ])->assertStatus(401)
          ->assertJson(['message' => 'Invalid credentials']);
    }

    public function test_user_endpoint_requires_authentication()
    {
        $this->getJson("{$this->prefix}/user")->assertStatus(401);
    }

    public function test_user_endpoint_returns_correct_user()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson("{$this->prefix}/user")
            ->assertOk()
            ->assertJson([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]);
    }

    public function test_logout_requires_authentication()
    {
        $this->postJson("{$this->prefix}/logout")->assertStatus(401);
    }

    public function test_successful_logout_deletes_current_token()
    {
        $user = User::factory()->create();
        $token = $user->createToken('TestToken')->plainTextToken;

        $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson("{$this->prefix}/logout")
            ->assertOk()
            ->assertJson(['message' => 'Logged out']);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
        ]);
    }

    public function test_successful_registration_returns_correct_structure()
    {
        $response = $this->postJson("{$this->prefix}/register", [
            'name' => 'Test User',
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);
    
        dd($response->status(), $response->json());
    
        $response->assertStatus(201)
            ->assertJsonStructure(['token'])
            ->assertJsonPath('token', fn ($token) => is_string($token) && strlen($token) > 0);
    }

    public function test_successful_login_returns_correct_structure()
    {
        $email = fake()->unique()->safeEmail();

        User::factory()->create([
            'email' => $email,
            'password' => bcrypt('password'),
        ]);

        $response = $this->postJson("{$this->prefix}/login", [
            'email' => $email,
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['token'])
            ->assertJsonPath('token', fn ($token) => is_string($token) && strlen($token) > 0);
    }
}