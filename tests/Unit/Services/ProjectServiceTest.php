<?php

namespace Tests\Unit\Services;

use App\Models\Project;
use App\Models\User;
use App\Services\ProjectService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;
use Exception;

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
        /** @var \Illuminate\Contracts\Auth\Authenticatable|\App\Models\User $user */
        $user = User::factory()->create();
        $this->actingAs($user);

        $project = Project::factory()->for($user)->create();
        $project->delete();

        $restored = $this->service->restore($project->id);

        $this->assertInstanceOf(Project::class, $restored);
        $this->assertFalse($restored->trashed());
    }

    public function test_restore_logs_exception_and_returns_null(): void
    {
        $mockProject = Mockery::mock(Project::class);
        $mockProject->shouldReceive('withTrashed')->once()->andReturnSelf();
        $mockProject->shouldReceive('findOrFail')->with(999)->once()->andThrow(new Exception('DB error'));

        Log::shouldReceive('error')
            ->once()
            ->with('Failed to restore project', Mockery::subset(['project_id' => 999]));

        $service = new ProjectService($mockProject);

        $result = $service->restore(999);

        $this->assertNull($result);
    }

    public function test_restore_returns_null_if_project_not_soft_deleted(): void
    {
        /** @var \Illuminate\Contracts\Auth\Authenticatable|\App\Models\User $user */
        $user = User::factory()->create();
        $this->actingAs($user);

        $project = Project::factory()->for($user)->create();

        $result = $this->service->restore($project->id);

        $this->assertNull($result);
    }

    public function test_restore_returns_null_if_project_does_not_belong_to_user(): void
    {
        /** @var \Illuminate\Contracts\Auth\Authenticatable|\App\Models\User $owner */
        $owner = User::factory()->create();

        /** @var \Illuminate\Contracts\Auth\Authenticatable|\App\Models\User $intruder */
        $intruder = User::factory()->create();

        $project = Project::factory()->for($owner)->create();
        $project->delete();

        $this->actingAs($intruder);

        $result = $this->service->restore($project->id);

        $this->assertNull($result);
    }
}