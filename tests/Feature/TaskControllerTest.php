<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_filtered_tasks(): void
    {
        $user    = User::factory()->create();
        $project = Project::factory()->for($user)->create();
        Task::factory()->for($project)->create(['priority' => 'high']);
        Task::factory()->for($project)->create(['priority' => 'low']);

        $this->actingAs($user)
            ->getJson("/api/projects/{$project->id}/tasks?priority=high")
            ->assertOk()
            ->assertJsonFragment(['priority' => 'high'])
            ->assertJsonMissing(['priority' => 'low']);
    }

    public function test_store_creates_task(): void
    {
        $user    = User::factory()->create();
        $project = Project::factory()->for($user)->create();

        $payload = [
            'title'    => 'Test Task',
            'priority' => 'medium',
            'done'     => false,
        ];

        $this->actingAs($user)
            ->postJson("/api/projects/{$project->id}/tasks", $payload)
            ->assertCreated()
            ->assertJsonFragment(['title' => 'Test Task']);
    }

    public function test_store_validation_fails(): void
    {
        $user    = User::factory()->create();
        $project = Project::factory()->for($user)->create();

        $this->actingAs($user)
            ->postJson("/api/projects/{$project->id}/tasks", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    public function test_show_returns_task(): void
    {
        $user    = User::factory()->create();
        $project = Project::factory()->for($user)->create();
        $task    = Task::factory()->for($project)->create();

        $this->actingAs($user)
            ->getJson("/api/projects/{$project->id}/tasks/{$task->id}")
            ->assertOk()
            ->assertJsonFragment(['id' => $task->id]);
    }

    public function test_update_modifies_task(): void
    {
        $user    = User::factory()->create();
        $project = Project::factory()->for($user)->create();
        $task    = Task::factory()->for($project)->create();

        $this->actingAs($user)
            ->patchJson("/api/projects/{$project->id}/tasks/{$task->id}", ['title' => 'Updated Title'])
            ->assertOk()
            ->assertJsonFragment(['title' => 'Updated Title']);
    }

    public function test_update_validation_fails(): void
    {
        $user    = User::factory()->create();
        $project = Project::factory()->for($user)->create();
        $task    = Task::factory()->for($project)->create();

        $this->actingAs($user)
            ->patchJson("/api/projects/{$project->id}/tasks/{$task->id}", ['title' => 123])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    public function test_destroy_deletes_task(): void
    {
        $user    = User::factory()->create();
        $project = Project::factory()->for($user)->create();
        $task    = Task::factory()->for($project)->create();

        $this->actingAs($user)
            ->deleteJson("/api/projects/{$project->id}/tasks/{$task->id}")
            ->assertOk()
            ->assertJson(['message' => 'Task deleted successfully']);
    }

    public function test_unauthorized_user_cannot_access_task(): void
    {
        $owner     = User::factory()->create();
        $intruder  = User::factory()->create();
        $project   = Project::factory()->for($owner)->create();
        $task      = Task::factory()->for($project)->create();

        $this->actingAs($intruder)
            ->getJson("/api/projects/{$project->id}/tasks/{$task->id}")
            ->assertForbidden();
    }

    public function test_guest_cannot_access_any_task_routes(): void
    {
        $project = Project::factory()->create();
        $task    = Task::factory()->for($project)->create();

        $this->getJson("/api/projects/{$project->id}/tasks")->assertUnauthorized();
        $this->postJson("/api/projects/{$project->id}/tasks", [])->assertUnauthorized();
        $this->getJson("/api/projects/{$project->id}/tasks/{$task->id}")->assertUnauthorized();
        $this->patchJson("/api/projects/{$project->id}/tasks/{$task->id}", [])->assertUnauthorized();
        $this->deleteJson("/api/projects/{$project->id}/tasks/{$task->id}")->assertUnauthorized();
    }
}
