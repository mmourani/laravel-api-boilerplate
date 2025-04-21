<?php

namespace Tests\Unit\Services;

use Exception;
use App\Models\Project;
use App\Services\ProjectService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class ProjectServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ProjectService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ProjectService();
    }

    public function test_restore_project_successfully(): void
    {
        $project = Project::factory()->create();
        $project->delete();

        $restored = $this->service->restore($project->id);

        $this->assertInstanceOf(Project::class, $restored);
        $this->assertFalse($restored->trashed());
    }

    public function test_restore_logs_exception_and_returns_null(): void
    {
        // Mock the Project model
        $mockProject = Mockery::mock(Project::class);

        $mockProject->shouldReceive('withTrashed')
            ->once()
            ->andReturnSelf();

        $mockProject->shouldReceive('findOrFail')
            ->with(999)
            ->once()
            ->andThrow(new Exception('DB error'));

        Log::shouldReceive('error')
            ->once()
            ->with('Failed to restore project', Mockery::type('array'));

        $service = new ProjectService($mockProject);

        $result = $service->restore(999);

        $this->assertNull($result);
    }
}
