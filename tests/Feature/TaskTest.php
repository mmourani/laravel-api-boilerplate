<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    /**
     * CREATION TESTS
     */
    public function test_user_can_create_task_in_their_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->postJson("/api/projects/{$project->id}/tasks", [
                'title' => 'Complete API tests',
                'priority' => 'high',
                'due_date' => '2025-05-01',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id', 'title', 'project_id', 'priority', 'due_date', 'done'
            ]);

        $this->assertDatabaseHas('tasks', [
            'title' => 'Complete API tests',
            'priority' => 'high',
            'project_id' => $project->id,
        ]);
    }

    public function test_user_cannot_create_task_in_other_users_project(): void
    {
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $anotherUser->id,
        ]);

        $response = $this->actingAs($user)
            ->postJson("/api/projects/{$project->id}/tasks", [
                'title' => 'This should fail',
                'priority' => 'high',
            ]);

        $response->assertStatus(403);
    }

    /**
     * READING/LISTING TESTS
     */
    public function test_user_can_get_tasks_for_their_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
        ]);
        
        // Create 3 tasks for the project
        Task::factory()->count(3)->create([
            'project_id' => $project->id,
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/projects/{$project->id}/tasks");

        $response->assertStatus(200)
            ->assertJsonCount(3);
    }

    public function test_user_cannot_get_tasks_from_other_users_project(): void
    {
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $anotherUser->id,
        ]);
        
        Task::factory()->count(3)->create([
            'project_id' => $project->id,
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/projects/{$project->id}/tasks");

        $response->assertStatus(403);
    }

    /**
     * UPDATE TESTS
     */
    public function test_user_can_update_task_in_their_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
        ]);
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'title' => 'Original Task',
            'priority' => 'low',
            'done' => false,
        ]);

        $response = $this->actingAs($user)
            ->putJson("/api/projects/{$project->id}/tasks/{$task->id}", [
                'title' => 'Updated Task',
                'priority' => 'high',
                'done' => true,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'id' => $task->id,
                'title' => 'Updated Task',
                'priority' => 'high',
                'done' => true,
            ]);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Updated Task',
            'priority' => 'high',
            'done' => true,
        ]);
    }

    public function test_user_cannot_update_task_in_other_users_project(): void
    {
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $anotherUser->id,
        ]);
        $task = Task::factory()->create([
            'project_id' => $project->id,
        ]);

        $response = $this->actingAs($user)
            ->putJson("/api/projects/{$project->id}/tasks/{$task->id}", [
                'title' => 'This should fail',
                'priority' => 'high',
            ]);

        $response->assertStatus(403);
    }

    /**
     * DELETE TESTS
     */
     
    /**
     * Test that a user can soft-delete a task from their project.
     */
    public function test_user_can_delete_task_from_their_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
        ]);
        $task = Task::factory()->create([
            'project_id' => $project->id,
        ]);
        
        $taskId = $task->id;
        
        $response = $this->actingAs($user)
            ->deleteJson("/api/projects/{$project->id}/tasks/{$task->id}");
            
        $response->assertStatus(200);
        
        // Verify the task was soft deleted
        $this->assertSoftDeleted('tasks', [
            'id' => $taskId,
        ]);
    }

    public function test_user_cannot_delete_task_from_other_users_project(): void
    {
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $anotherUser->id,
        ]);
        $task = Task::factory()->create([
            'project_id' => $project->id,
        ]);

        $response = $this->actingAs($user)
            ->deleteJson("/api/projects/{$project->id}/tasks/{$task->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
        ]);
    }
    
    /**
     * Test that soft-deleted tasks are not visible in task listings.
     */
    public function test_soft_deleted_tasks_are_not_visible_in_list(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
        ]);
        
        // Create and soft delete a task
        $softDeletedTask = Task::factory()->create([
            'project_id' => $project->id,
            'title' => 'Soft Deleted Task'
        ]);
        $softDeletedTask->delete();
        
        // Create an active task
        $activeTask = Task::factory()->create([
            'project_id' => $project->id,
            'title' => 'Active Task'
        ]);
        
        // Get the project's tasks
        $response = $this->actingAs($user)
            ->getJson("/api/projects/{$project->id}/tasks");
        
        $response->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonFragment([
                'id' => $activeTask->id,
                'title' => 'Active Task'
            ])
            ->assertJsonMissing([
                'title' => 'Soft Deleted Task'
            ]);
    }

    /**
     * FILTERING TESTS
     */
    public function test_tasks_can_be_filtered_by_priority(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
        ]);
        
        // Create tasks with different priorities
        Task::factory()->create([
            'project_id' => $project->id,
            'priority' => 'high',
        ]);
        Task::factory()->create([
            'project_id' => $project->id,
            'priority' => 'medium',
        ]);
        Task::factory()->count(2)->create([
            'project_id' => $project->id,
            'priority' => 'low',
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/projects/{$project->id}/tasks?priority=low");

        $response->assertStatus(200)
            ->assertJsonCount(2);
        
        // Verify all returned tasks have priority=low
        $responseData = $response->json();
        foreach ($responseData as $task) {
            $this->assertEquals('low', $task['priority']);
        }
    }

    public function test_tasks_can_be_filtered_by_completion_status(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
        ]);
        
        // Create tasks with different completion statuses
        Task::factory()->count(2)->create([
            'project_id' => $project->id,
            'done' => true,
        ]);
        Task::factory()->count(3)->create([
            'project_id' => $project->id,
            'done' => false,
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/projects/{$project->id}/tasks?done=true");

        $response->assertStatus(200)
            ->assertJsonCount(2);
        
        // Verify all returned tasks have done=true
        $responseData = $response->json();
        foreach ($responseData as $task) {
            $this->assertTrue($task['done']);
        }
    }

    public function test_tasks_can_be_filtered_by_due_date(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
        ]);
        
        // Create tasks with different due dates
        $futureDate = now()->addDays(30)->format('Y-m-d');
        Task::factory()->create([
            'project_id' => $project->id,
            'due_date' => now()->addDays(5),
        ]);
        Task::factory()->count(2)->create([
            'project_id' => $project->id,
            'due_date' => now()->addDays(30),
        ]);
        Task::factory()->create([
            'project_id' => $project->id,
            'due_date' => now()->addDays(60),
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/projects/{$project->id}/tasks?due_date={$futureDate}");

        $response->assertStatus(200)
            ->assertJsonCount(2);
    }

    /**
     * SORTING TESTS
     */
    public function test_tasks_can_be_sorted_by_priority(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
        ]);
        
        // Create tasks with different priorities in a specific order
        // SQLite may order these differently than MySQL when using orderByRaw,
        // so we'll test the endpoint behavior more generically
        $highTask = Task::factory()->create([
            'project_id' => $project->id,
            'priority' => 'high',
            'title' => 'High priority task',
        ]);
        
        $mediumTask = Task::factory()->create([
            'project_id' => $project->id,
            'priority' => 'medium',
            'title' => 'Medium priority task',
        ]);
        
        $lowTask = Task::factory()->create([
            'project_id' => $project->id,
            'priority' => 'low', 
            'title' => 'Low priority task',
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/projects/{$project->id}/tasks?sort_by=priority&direction=desc");

        $response->assertStatus(200)
            ->assertJsonCount(3);
        
        $responseData = $response->json();
        
        // For descending order (high→medium→low), simply verify positions directly
        // This is more reliable and easier to understand than index comparisons
        $this->assertEquals('high', $responseData[0]['priority']);
        $this->assertEquals('medium', $responseData[1]['priority']);
        $this->assertEquals('low', $responseData[2]['priority']);
    }

    public function test_tasks_can_be_sorted_by_due_date(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
        ]);
        
        // Create tasks with different due dates
        $nearDueDate = now()->addDays(1);
        $midDueDate = now()->addDays(15);
        $farDueDate = now()->addDays(30);
        
        Task::factory()->create([
            'project_id' => $project->id,
            'due_date' => $midDueDate,
            'title' => 'Mid-term task',
        ]);
        Task::factory()->create([
            'project_id' => $project->id,
            'due_date' => $farDueDate,
            'title' => 'Long-term task',
        ]);
        Task::factory()->create([
            'project_id' => $project->id,
            'due_date' => $nearDueDate,
            'title' => 'Urgent task',
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/projects/{$project->id}/tasks?sort_by=due_date&direction=asc");

        $response->assertStatus(200)
            ->assertJsonCount(3);
        
        $responseData = $response->json();
        
        // Expect nearest due date first
        $this->assertEquals('Urgent task', $responseData[0]['title']);
        $this->assertEquals('Mid-term task', $responseData[1]['title']);
        $this->assertEquals('Long-term task', $responseData[2]['title']);
    }

    /**
     * TEST ERROR HANDLING PATHS
     */
    public function test_task_not_found_in_project(): void
    {
        $user = User::factory()->create();
        $project1 = Project::factory()->create(['user_id' => $user->id]);
        $project2 = Project::factory()->create(['user_id' => $user->id]);
        $task = Task::factory()->create(['project_id' => $project2->id]);

        // Try to access a task that exists but is not in this project
        $response = $this->actingAs($user)
            ->getJson("/api/projects/{$project1->id}/tasks/{$task->id}");

        $response->assertStatus(404)
            ->assertJson(['message' => 'Task not found in this project']);
    }

    public function test_task_validation_invalid_priority(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->postJson("/api/projects/{$project->id}/tasks", [
                'title' => 'Invalid Priority Task',
                'priority' => 'invalid-priority', // Not in allowed list
                'due_date' => '2025-05-01',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['priority']);
    }

    public function test_task_validation_missing_title(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->postJson("/api/projects/{$project->id}/tasks", [
                'priority' => 'high',
                'due_date' => '2025-05-01',
                // Missing title
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    public function test_task_validation_invalid_date_format(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->postJson("/api/projects/{$project->id}/tasks", [
                'title' => 'Date Format Task',
                'priority' => 'high',
                'due_date' => 'not-a-date', // Invalid date format
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['due_date']);
    }

    public function test_task_update_not_found_in_project(): void
    {
        $user = User::factory()->create();
        $project1 = Project::factory()->create(['user_id' => $user->id]);
        $project2 = Project::factory()->create(['user_id' => $user->id]);
        $task = Task::factory()->create(['project_id' => $project2->id]);

        $response = $this->actingAs($user)
            ->putJson("/api/projects/{$project1->id}/tasks/{$task->id}", [
                'title' => 'Updated Task',
                'priority' => 'high',
            ]);

        $response->assertStatus(404)
            ->assertJson(['message' => 'Task not found in this project']);
    }

    public function test_task_delete_not_found_in_project(): void
    {
        $user = User::factory()->create();
        $project1 = Project::factory()->create(['user_id' => $user->id]);
        $project2 = Project::factory()->create(['user_id' => $user->id]);
        $task = Task::factory()->create(['project_id' => $project2->id]);

        $response = $this->actingAs($user)
            ->deleteJson("/api/projects/{$project1->id}/tasks/{$task->id}");

        $response->assertStatus(404)
            ->assertJson(['message' => 'Task not found in this project']);
    }

    /**
     * ADDITIONAL SORTING/FILTERING TESTS
     */
    public function test_tasks_can_be_sorted_by_invalid_field(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);
        
        // Create 3 tasks
        $task1 = Task::factory()->create([
            'project_id' => $project->id,
            'title' => 'Task One'
        ]);
        
        $task2 = Task::factory()->create([
            'project_id' => $project->id,
            'title' => 'Task Two'
        ]);
        
        $task3 = Task::factory()->create([
            'project_id' => $project->id,
            'title' => 'Task Three'
        ]);

        // Even with invalid sort field, request should succeed
        $response = $this->actingAs($user)
            ->getJson("/api/projects/{$project->id}/tasks?sort_by=invalid_field&direction=asc");

        // Verify the response status and task count
        $response->assertStatus(200)
            ->assertJsonCount(3);
        
        $responseData = $response->json();
        
        // Verify all tasks are present by checking their titles exist in the response
        $titles = array_column($responseData, 'title');
        $this->assertContains('Task One', $titles);
        $this->assertContains('Task Two', $titles);
        $this->assertContains('Task Three', $titles);
        
        // Verify each task has the expected structure
        foreach ($responseData as $task) {
            $this->assertArrayHasKey('id', $task);
            $this->assertArrayHasKey('title', $task);
            $this->assertArrayHasKey('project_id', $task);
            $this->assertArrayHasKey('done', $task);
            $this->assertArrayHasKey('priority', $task);
            
            // Verify task belongs to the correct project
            $this->assertEquals($project->id, $task['project_id']);
        }
        
        // Verify that we can consistently get the same results with the same query
        $repeatResponse = $this->actingAs($user)
            ->getJson("/api/projects/{$project->id}/tasks?sort_by=invalid_field&direction=asc");
        
        $this->assertEquals(
            $response->json(), 
            $repeatResponse->json(),
            "Sorting should be consistent across multiple identical requests"
        );
    }

    public function test_tasks_with_invalid_sorting_direction(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);
        
        Task::factory()->count(3)->create(['project_id' => $project->id]);

        // Even with invalid direction, request should succeed with default direction
        $response = $this->actingAs($user)
            ->getJson("/api/projects/{$project->id}/tasks?sort_by=id&direction=invalid");

        $response->assertStatus(200)
            ->assertJsonCount(3);
        
        // Default direction is asc, so first ID should be 1
        $responseData = $response->json();
        $this->assertEquals(1, $responseData[0]['id']);
    }

    public function test_combined_filters_and_sorting(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);
        
        // Create tasks with different combinations of priority and done status
        Task::factory()->create([
            'project_id' => $project->id,
            'priority' => 'high',
            'done' => true,
            'title' => 'Completed high priority task',
            'due_date' => now()->addDays(5)
        ]);
        
        Task::factory()->create([
            'project_id' => $project->id,
            'priority' => 'high',
            'done' => false,
            'title' => 'Pending high priority task',
            'due_date' => now()->addDays(2)
        ]);
        
        Task::factory()->create([
            'project_id' => $project->id,
            'priority' => 'medium',
            'done' => true,
            'title' => 'Completed medium priority task',
            'due_date' => now()->addDays(10)
        ]);

        // Test combined filtering: only high priority and pending tasks
        $response = $this->actingAs($user)
            ->getJson("/api/projects/{$project->id}/tasks?priority=high&done=false&sort_by=due_date&direction=asc");

        $response->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonFragment(['title' => 'Pending high priority task']);
    }
}

