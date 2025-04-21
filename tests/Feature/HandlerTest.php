<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_model_not_found_returns_404(): void
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->createOne();

        $this->actingAs($user)
            ->getJson('/api/v1/simulate-model-not-found')
            ->assertStatus(404)
            ->assertJsonFragment([
                'message' => 'No query results for model [App\Models\Project] 999999',
            ]);
    }

    public function test_query_exception_returns_500(): void
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->createOne();

        $this->actingAs($user)
            ->getJson('/api/v1/simulate-db-error')
            ->assertStatus(500)
            ->assertJsonPath('message', 'Simulated SQL error');
    }

    public function test_http_not_found_returns_404(): void
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->createOne();

        $this->actingAs($user)
            ->getJson('/api/v1/simulate-http-not-found')
            ->assertStatus(404)
            ->assertJsonFragment([
                'message' => 'Not found',
            ]);
    }

    public function test_generic_error_returns_500(): void
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->createOne();

        $this->actingAs($user)
            ->getJson('/api/v1/simulate-generic-error')
            ->assertStatus(500)
            ->assertJsonFragment([
                'message' => 'Server error',
            ]);
    }

    public function test_validation_exception_returns_422(): void
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->createOne();

        $this->actingAs($user)
            ->postJson('/api/v1/simulate-validation-error', [])
            ->assertStatus(422)
            ->assertJsonStructure([
                'message', 'errors' => ['name']
            ]);
    }

    public function test_authentication_exception_returns_401(): void
    {
        $this->getJson('/api/v1/simulate-auth-error')
            ->assertStatus(401)
            ->assertJsonFragment([
                'message' => 'Unauthenticated.',
            ]);
    }

    public function test_authorization_exception_returns_403(): void
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->createOne();

        $this->actingAs($user)
            ->getJson('/api/v1/simulate-authorization-error')
            ->assertStatus(403)
            ->assertJsonFragment([
                'message' => 'You are not allowed to access this resource.',
            ]);
    }

    public function test_throttle_exception_returns_429(): void
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->createOne();

        $this->actingAs($user)
            ->getJson('/api/v1/simulate-throttle')
            ->assertStatus(429)
            ->assertJsonFragment([
                'message' => 'Too many requests.',
            ]);
    }
}