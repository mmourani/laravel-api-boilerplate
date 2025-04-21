<?php

namespace Tests\Unit\Models;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();

        $this->assertInstanceOf(User::class, $project->user);
        $this->assertEquals($user->id, $project->user->id);
    }

    public function test_project_has_many_tasks(): void
    {
        $project = Project::factory()->create();
        $task1 = Task::factory()->for($project)->create();
        $task2 = Task::factory()->for($project)->create();

        $this->assertCount(2, $project->tasks);
        $this->assertTrue($project->tasks->contains($task1));
        $this->assertTrue($project->tasks->contains($task2));
    }
}