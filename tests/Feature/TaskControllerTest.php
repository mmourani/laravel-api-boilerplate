<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Tests\TestCase;

class TaskControllerTest extends TestCase
{

    public function test_index_returns_filtered_tasks(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        /** @var Project $project */
        $project = Project::factory()->for($user)->create();

        Task::factory()->for($project)->create(['priority' => 'high']);
        Task::factory()->for($project)->create(['priority' => 'low']);

        $this->actingAs($user)
            ->getJson("/api/v1/projects/{$project->id}/tasks?priority=high")
            ->assertOk()
            ->assertJsonFragment(['priority' => 'high'])
            ->assertJsonMissing(['priority' => 'low']);
    }

    public function test_store_creates_task(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        /** @var Project $project */
        $project = Project::factory()->for($user)->create();

        $payload = [
            'title'    => 'Test Task',
            'priority' => 'medium',
            'done'     => false,
        ];

        $this->actingAs($user)
            ->postJson("/api/v1/projects/{$project->id}/tasks", $payload)
            ->assertCreated()
            ->assertJsonFragment(['title' => 'Test Task']);
    }

    public function test_store_validation_fails(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        /** @var Project $project */
        $project = Project::factory()->for($user)->create();

        $this->actingAs($user)
            ->postJson("/api/v1/projects/{$project->id}/tasks", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    public function test_show_returns_task(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        /** @var Project $project */
        $project = Project::factory()->for($user)->create();

        /** @var Task $task */
        $task = Task::factory()->for($project)->create();

        $this->actingAs($user)
            ->getJson("/api/v1/projects/{$project->id}/tasks/{$task->id}")
            ->assertOk()
            ->assertJsonFragment(['id' => $task->id]);
    }

    public function test_update_modifies_task(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        /** @var Project $project */
        $project = Project::factory()->for($user)->create();

        /** @var Task $task */
        $task = Task::factory()->for($project)->create();

        $this->actingAs($user)
            ->patchJson("/api/v1/projects/{$project->id}/tasks/{$task->id}", ['title' => 'Updated Title'])
            ->assertOk()
            ->assertJsonFragment(['title' => 'Updated Title']);
    }

    public function test_update_validation_fails(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        /** @var Project $project */
        $project = Project::factory()->for($user)->create();

        /** @var Task $task */
        $task = Task::factory()->for($project)->create();

        $this->actingAs($user)
            ->patchJson("/api/v1/projects/{$project->id}/tasks/{$task->id}", ['title' => 123])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    public function test_destroy_deletes_task(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        /** @var Project $project */
        $project = Project::factory()->for($user)->create();

        /** @var Task $task */
        $task = Task::factory()->for($project)->create();

        $this->actingAs($user)
            ->deleteJson("/api/v1/projects/{$project->id}/tasks/{$task->id}")
            ->assertOk()
            ->assertJson(['message' => 'Task deleted successfully']);
    }

    public function test_unauthorized_user_cannot_access_task(): void
    {
        /** @var User $owner */
        $owner = User::factory()->create();

        /** @var User $intruder */
        $intruder = User::factory()->create();

        /** @var Project $project */
        $project = Project::factory()->for($owner)->create();

        /** @var Task $task */
        $task = Task::factory()->for($project)->create();

        $this->actingAs($intruder)
            ->getJson("/api/v1/projects/{$project->id}/tasks/{$task->id}")
            ->assertForbidden();
    }

    public function test_guest_cannot_access_any_task_routes(): void
    {
        /** @var Project $project */
        $project = Project::factory()->create();

        /** @var Task $task */
        $task = Task::factory()->for($project)->create();

        $this->getJson("/api/v1/projects/{$project->id}/tasks")->assertUnauthorized();
        $this->postJson("/api/v1/projects/{$project->id}/tasks", [])->assertUnauthorized();
        $this->getJson("/api/v1/projects/{$project->id}/tasks/{$task->id}")->assertUnauthorized();
        $this->patchJson("/api/v1/projects/{$project->id}/tasks/{$task->id}", [])->assertUnauthorized();
        $this->deleteJson("/api/v1/projects/{$project->id}/tasks/{$task->id}")->assertUnauthorized();
    }
}