<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectControllerTest extends TestCase
{
    use RefreshDatabase;

    protected string $prefix = '/api/v1';

    public function test_restore_successfully(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        /** @var Project $project */
        $project = Project::factory()->create(['user_id' => $user->id]);
        $project->delete();

        $this->actingAs($user);

        $response = $this->patchJson("{$this->prefix}/projects/{$project->id}/restore");

        $response->assertOk()
            ->assertJson(['message' => 'Project restored successfully']);

        $this->assertFalse($project->fresh()->trashed());
    }

    public function test_restore_unauthorized_user(): void
    {
        /** @var User $owner */
        $owner = User::factory()->create();

        /** @var User $intruder */
        $intruder = User::factory()->create();

        /** @var Project $project */
        $project = Project::factory()->create(['user_id' => $owner->id]);
        $project->delete();

        $this->actingAs($intruder);

        $response = $this->patchJson("{$this->prefix}/projects/{$project->id}/restore");

        $response->assertStatus(403);
    }

    public function test_restore_already_active_project(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        /** @var Project $project */
        $project = Project::factory()->create(['user_id' => $user->id]); // Not deleted

        $this->actingAs($user);

        $response = $this->patchJson("{$this->prefix}/projects/{$project->id}/restore");

        $response->assertStatus(400)
            ->assertJson(['message' => 'Project is not deleted']);
    }

    public function test_restore_nonexistent_project(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->patchJson("{$this->prefix}/projects/999999/restore");

        $response->assertStatus(404)
            ->assertJsonFragment(['message' => 'Project not found']);
    }

    public function test_update_with_partial_data(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        /** @var Project $project */
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'title' => 'Initial Title',
        ]);

        $this->actingAs($user);

        $response = $this->patchJson("{$this->prefix}/projects/{$project->id}", [
            'title' => 'Updated Title',
        ]);

        $response->assertOk();
        $this->assertEquals('Updated Title', $project->fresh()->title);
    }

    public function test_index_with_empty_search_results(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        Project::factory()->create([
            'user_id' => $user->id,
            'title' => 'Alpha',
        ]);

        $this->actingAs($user);

        $response = $this->getJson("{$this->prefix}/projects?search=Beta");

        $response->assertOk();
        $this->assertCount(0, $response->json('data'));
    }
}