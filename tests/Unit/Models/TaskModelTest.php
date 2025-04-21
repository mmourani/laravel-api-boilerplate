<?php

namespace Tests\Unit\Models;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_task_belongs_to_project(): void
    {
        $project = Project::factory()->create();
        $task = Task::factory()->for($project)->create();

        $this->assertTrue($task->project->is($project));
    }

    public function test_task_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();
        $task = Task::factory()->for($project)->create();

        $this->assertTrue($task->project->user->is($user));
    }

    public function test_task_can_be_marked_done(): void
    {
        $task = Task::factory()->create(['is_done' => false]);

        $task->update(['is_done' => true]);

        $this->assertTrue($task->fresh()->is_done);
    }

    public function test_task_has_title_and_priority(): void
    {
        $task = Task::factory()->create([
            'title' => 'Test Task',
            'priority' => 'high',
        ]);

        $this->assertEquals('Test Task', $task->title);
        $this->assertEquals('high', $task->priority);
    }

    public function test_task_casts_and_dates(): void
    {
        $task = Task::factory()->create([
            'deadline' => now()->addDays(3),
            'is_done' => true,
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $task->deadline);
        $this->assertTrue($task->is_done);
    }
}