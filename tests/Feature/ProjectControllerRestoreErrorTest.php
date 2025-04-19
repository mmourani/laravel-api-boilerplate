<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Project;
use App\Services\ProjectService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Tests\TestCase;

class ProjectControllerRestoreErrorTest extends TestCase
{
    use RefreshDatabase;

    public function test_restore_handles_database_error()
    {
        Log::shouldReceive('error');

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Create a real soft-deleted project
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'deleted_at' => now(),
        ]);

        // Mock the service to simulate DB failure when restore is called
        $mockService = Mockery::mock(ProjectService::class);
        $mockService->shouldReceive('restore')
            ->with($project->id)
            ->andThrow(new \Illuminate\Database\QueryException(
                'sqlite', 'UPDATE projects SET deleted_at = NULL WHERE id = ?', [$project->id],
                new \Exception('Simulated DB error')
            ));

        $this->app->instance(ProjectService::class, $mockService);

        $response = $this->patchJson("/api/projects/{$project->id}/restore");

        $response->assertStatus(500)
            ->assertJson(['message' => 'Error restoring project']);
    }
}
