<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Project;
use App\Services\ProjectService;
use Mockery;
use Exception;

class ProjectControllerRestoreErrorTest extends TestCase
{

    public function test_restore_handles_database_error(): void
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create();

        /** @var \App\Models\Project $project */
        $project = Project::factory()->for($user)->create();
        $project->delete();

        $this->actingAs($user);

        // Mock the ProjectService to simulate a DB failure
        $mockService = Mockery::mock(ProjectService::class);
        $mockService->shouldReceive('restore')
            ->with($project->id)
            ->once()
            ->andThrow(new Exception('Simulated DB error'));

        $this->app->instance(ProjectService::class, $mockService);

        $response = $this->patchJson("/api/v1/projects/{$project->id}/restore");

        $response->assertStatus(500)
            ->assertJson([
                'message' => 'Failed to restore project',
            ]);
    }
}