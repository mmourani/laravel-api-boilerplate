<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_restore_successfully(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        // Soft delete
        $project->delete();

        // Ensure project is actually soft-deleted
        $this->assertTrue($project->fresh()->trashed(), 'The project is not soft-deleted before restore.');

        $this->actingAs($user);

        $response = $this->patchJson("/api/projects/{$project->id}/restore");

        $response->assertOk()
            ->assertJson([
                'message' => 'Project restored successfully',
            ]);

        $this->assertFalse($project->fresh()->trashed());
    }

    public function test_restore_unauthorized_user()
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $project = Project::factory()->for($owner)->create();
        $project->delete();

        $this->actingAs($otherUser);

        $response = $this->patchJson("/api/projects/{$project->id}/restore");

        $response->assertStatus(403);
    }

    public function test_restore_already_active_project()
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();

        $this->actingAs($user);

        $response = $this->patchJson("/api/projects/{$project->id}/restore");

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Project is not deleted',
            ]);
    }

    public function test_restore_nonexistent_project()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->patchJson('/api/projects/99999/restore');

        $response->assertStatus(404)
            ->assertJsonFragment([
                'message' => 'Project not found',
            ]);
    }

    public function test_update_with_partial_data()
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();

        $this->actingAs($user);

        $response = $this->patchJson("/api/projects/{$project->id}", [
            'title' => 'Updated Title',
        ]);

        $response->assertOk();
        $this->assertEquals('Updated Title', $project->fresh()->title);
    }

    public function test_index_with_empty_search_results()
    {
        $user = User::factory()->create();
        Project::factory()->for($user)->create(['title' => 'Alpha']);

        $this->actingAs($user);

        $response = $this->getJson('/api/projects?search=Beta');

        $response->assertOk();
        $this->assertCount(0, $response->json('data'));
    }
}
