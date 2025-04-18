<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    /**
     * CREATION TESTS
     */
    public function test_user_can_create_project(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/projects', [
                'title' => 'My Test Project',
                'description' => 'This is a test project description',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'title',
                'description',
                'user_id',
                'created_at',
                'updated_at',
            ]);

        $this->assertDatabaseHas('projects', [
            'title' => 'My Test Project',
            'description' => 'This is a test project description',
            'user_id' => $user->id,
        ]);
    }

    public function test_user_cannot_create_project_with_invalid_data(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/projects', [
                'title' => '',
                'description' => '',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    public function test_unauthenticated_user_cannot_create_project(): void
    {
        $response = $this->postJson('/api/projects', [
            'title' => 'My Test Project',
            'description' => 'This is a test project description',
        ]);

        $response->assertStatus(401);
    }

    /**
     * READING/LISTING TESTS
     */
    public function test_user_can_get_their_projects(): void
    {
        $user = User::factory()->create();
        $projects = Project::factory()->count(3)->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/projects');

        $response->assertStatus(200)
            ->assertJsonCount(3)
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'title',
                    'description',
                    'user_id',
                    'created_at',
                    'updated_at',
                ],
            ]);
    }

    public function test_user_cannot_see_other_users_projects(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        Project::factory()->count(3)->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/projects');

        $response->assertStatus(200)
            ->assertJsonCount(0);
    }

    public function test_user_can_get_specific_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/projects/{$project->id}");

        $response->assertStatus(200)
            ->assertJson([
                'id' => $project->id,
                'title' => $project->title,
                'description' => $project->description,
                'user_id' => $user->id,
            ]);
    }

    public function test_user_cannot_get_other_users_project(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/projects/{$project->id}");

        $response->assertStatus(403);
    }

    /**
     * UPDATE TESTS
     */
    public function test_user_can_update_their_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->putJson("/api/projects/{$project->id}", [
                'title' => 'Updated Project',
                'description' => 'Updated description',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'title' => 'Updated Project',
                'description' => 'Updated description',
            ]);

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'title' => 'Updated Project',
            'description' => 'Updated description',
        ]);
    }

    public function test_user_cannot_update_other_users_project(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($user)
            ->putJson("/api/projects/{$project->id}", [
                'title' => 'Updated Project',
            ]);

        $response->assertStatus(403);
    }

    /**
     * DELETE TESTS
     */
    public function test_user_can_delete_their_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->deleteJson("/api/projects/{$project->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('projects', [
            'id' => $project->id,
        ]);
    }

    public function test_user_cannot_delete_other_users_project(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($user)
            ->deleteJson("/api/projects/{$project->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
        ]);
    }
}

