<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Services\ProjectService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectControllerRestoreErrorTest extends TestCase
{
    use RefreshDatabase;

    public function test_restore_handles_database_error(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);
        $project->delete();

        $this->actingAs($user);

        // Create a project model to return (same user)
        $fakedProject = new Project([
            'id' => $project->id,
            'user_id' => $user->id,
            'deleted_at' => now(), // mimic soft-deleted
        ]);

        // Simulate service throwing a DB error
        $mockService = \Mockery::mock(ProjectService::class);
        $mockService->shouldReceive('restore')
            ->with($project->id)
            ->once()
            ->andThrow(new \Exception('Simulated DB error'));

        $this->app->instance(ProjectService::class, $mockService);

        $response = $this->patchJson("/api/projects/{$project->id}/restore");

        $response->assertStatus(500)
            ->assertJson(['message' => 'Failed to restore project']);
    }
}
