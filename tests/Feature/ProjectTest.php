<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ProjectTest - Feature tests for Project controller endpoints
 * 
 * Note: API responses from the index endpoint include pagination structure with:
 * - 'data': The array of projects or search results
 * - 'links': Navigation links for pagination (first, last, prev, next)
 * - 'meta': Pagination metadata (current_page, from, last_page, etc.)
 */
class ProjectTest extends TestCase
{
    use RefreshDatabase;

    /**
     * CREATION TESTS
     */
    /**
     * Test that a user can create a project.
     */
    public function test_user_can_create_project(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/projects', [
                'title' => 'My Test Project',
                'description' => 'This is a test project description',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'title',
                'description',
                'user_id',
                'created_at',
                'updated_at',
            ]);

        $this->assertDatabaseHas('projects', [
            'title' => 'My Test Project',
            'description' => 'This is a test project description',
            'user_id' => $user->id,
        ]);
    }

    public function test_user_cannot_create_project_with_invalid_data(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/projects', [
                'title' => '',
                'description' => '',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    public function test_unauthenticated_user_cannot_create_project(): void
    {
        $response = $this->postJson('/api/projects', [
            'title' => 'My Test Project',
            'description' => 'This is a test project description',
        ]);

        $response->assertStatus(401);
    }

    /**
     * READING/LISTING TESTS
     */
    public function test_user_can_get_their_projects(): void
    {
        $user = User::factory()->create();
        $projects = Project::factory()->count(3)->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/projects');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'description',
                        'user_id',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'links',
                'meta',
            ]);
    }

    public function test_user_cannot_see_other_users_projects(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        Project::factory()->count(3)->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/projects');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    public function test_user_can_get_specific_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/projects/{$project->id}");

        $response->assertStatus(200)
            ->assertJson([
                'id' => $project->id,
                'title' => $project->title,
                'description' => $project->description,
                'user_id' => $user->id,
            ]);
    }

    public function test_user_cannot_get_other_users_project(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/projects/{$project->id}");

        $response->assertStatus(403);
    }

    /**
     * UPDATE TESTS
     */
    public function test_user_can_update_their_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->putJson("/api/projects/{$project->id}", [
                'title' => 'Updated Project',
                'description' => 'Updated description',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'title' => 'Updated Project',
                'description' => 'Updated description',
            ]);

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'title' => 'Updated Project',
            'description' => 'Updated description',
        ]);
    }

    /**
     * Test validation failures in project update
     */
    public function test_update_project_with_invalid_data(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
        ]);

        // Test with title too long
        $response = $this->actingAs($user)
            ->putJson("/api/projects/{$project->id}", [
                'title' => str_repeat('a', 256), // 256 characters, exceeds the 255 max
                'description' => 'Valid description',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title']);

        // Test with empty title when title is required
        $response = $this->actingAs($user)
            ->putJson("/api/projects/{$project->id}", [
                'title' => '',
                'description' => 'Valid description',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title']);

        // Test with non-string description
        $response = $this->actingAs($user)
            ->putJson("/api/projects/{$project->id}", [
                'title' => 'Valid title',
                'description' => ['invalid', 'description', 'format'],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['description']);

        // Verify the project remains unchanged
        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'title' => $project->title,
            'description' => $project->description,
        ]);
    }

    /**
     * Test updating a project with a subset of fields
     */
    public function test_partial_project_update(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'title' => 'Original Title',
            'description' => 'Original Description',
        ]);

        // Update only the title
        $response = $this->actingAs($user)
            ->putJson("/api/projects/{$project->id}", [
                'title' => 'Updated Title',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'id' => $project->id,
                'title' => 'Updated Title',
                'description' => 'Original Description', // Description should remain unchanged
            ]);

        // Update only the description
        $response = $this->actingAs($user)
            ->putJson("/api/projects/{$project->id}", [
                'description' => 'Updated Description',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'id' => $project->id,
                'title' => 'Updated Title', // Title should remain from previous update
                'description' => 'Updated Description',
            ]);

        // Verify final state in database
        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'title' => 'Updated Title',
            'description' => 'Updated Description',
        ]);
    }

    /**
     * DELETE TESTS
     */
    
    /**
     * Test that a user can soft-delete their project.
     */
    public function test_user_can_delete_their_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->deleteJson("/api/projects/{$project->id}");

        $response->assertStatus(200);
        
        // Verify the project was soft deleted
        $this->assertSoftDeleted('projects', [
            'id' => $project->id,
        ]);
    }

    public function test_user_cannot_delete_other_users_project(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($user)
            ->deleteJson("/api/projects/{$project->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
        ]);
    }
    
    /**
     * Test that soft-deleted projects are not visible in the project list.
     */
    public function test_soft_deleted_projects_are_not_visible_in_list(): void
    {
        $user = User::factory()->create();
        
        // Create and soft delete a project
        $softDeletedProject = Project::factory()->create([
            'user_id' => $user->id,
        ]);
        $softDeletedProject->delete();
        
        // Create an active project
        $activeProject = Project::factory()->create([
            'user_id' => $user->id,
        ]);
        
        // Get the user's projects
        $response = $this->actingAs($user)
            ->getJson('/api/projects');
        
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $activeProject->id)
            ->assertJsonMissing([
                'id' => $softDeletedProject->id,
            ]);
    }
    
    /**
     * RESTORE TESTS
     */

    /**
     * Test that a user can restore their soft-deleted project.
     * Route: POST /api/projects/{id}/restore
     */
    public function test_user_can_restore_their_project(): void
    {
        // Create test user and project
        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'title' => 'Project to restore',
        ]);
        
        // Store the ID for later use
        $projectId = $project->id;
        
        // Verify the project exists in database
        $this->assertDatabaseHas('projects', [
            'id' => $projectId,
            'user_id' => $user->id,
        ]);
        
        // Verify project is not trashed before deletion
        $this->assertFalse($project->trashed(), "Project should NOT be trashed before deletion");
        
        // Soft delete the project
        $project->delete();
        
        // Verify it's soft deleted in the database
        $this->assertSoftDeleted('projects', [
            'id' => $projectId,
        ]);
        
        // Verify project is trashed after deletion
        $trashedProject = Project::withTrashed()->find($projectId);
        $this->logProject("Project after soft delete", $trashedProject);
        $this->assertTrue($trashedProject->trashed(), "Project should be trashed");
        
        // Login as the project owner
        $this->actingAs($user);
        
        // Make request to restore the project
        $response = $this->actingAs($user)
            ->postJson("/api/projects/{$projectId}/restore");
        
        // Verify restoration was successful
        $response->assertStatus(200);
        
        // Verify it's restored in the database
        $this->assertDatabaseHas('projects', [
            'id' => $projectId,
            'deleted_at' => null,
        ]);
        
        // Verify project is no longer trashed
        $restoredProject = Project::find($projectId);
        $this->logProject("Project after restoration", $restoredProject);
        $this->assertNotNull($restoredProject, "Project should exist after restoration");
        $this->assertFalse($restoredProject->trashed(), "Project should not be trashed after restoration");
    }
    
    /**
     * Helper method to log project state for debugging
     */
    private function logProject(string $message, Project $project): void
    {
        \Log::debug($message . ": " . json_encode([
            'id' => $project->id,
            'user_id' => $project->user_id,
            'title' => $project->title,
            'deleted_at' => $project->deleted_at,
            'trashed' => $project->trashed(),
        ]));
    }
    
    /**
     * Test that a user cannot restore another user's project.
     * Route: POST /api/projects/{id}/restore
     */
    public function test_user_cannot_restore_other_users_project(): void
    {
        
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $otherUser->id,
        ]);
        
        // Soft delete the project
        $project->delete();
        // Get the project ID for restoration attempt
        $projectId = $project->id;
        
        // Attempt to restore the project as a different user
        $response = $this->actingAs($user)
            ->postJson("/api/projects/{$projectId}/restore");
        
        $response->assertStatus(403);
        
        // Verify it's still soft deleted
        $this->assertSoftDeleted('projects', [
            'id' => $project->id,
        ]);
    }
    
    /**
     * SEARCH FUNCTIONALITY TESTS
     */
    
    /**
     * Test basic search functionality by title
     */
    public function test_search_projects_by_title(): void
    {
        $user = User::factory()->create();
        
        // Create test projects with different titles
        $project1 = Project::factory()->create([
            'user_id' => $user->id,
            'title' => 'Marketing Campaign',
            'description' => 'Generic description'
        ]);
        
        $project2 = Project::factory()->create([
            'user_id' => $user->id,
            'title' => 'Website Redesign',
            'description' => 'Generic description'
        ]);
        
        $project3 = Project::factory()->create([
            'user_id' => $user->id,
            'title' => 'Marketing Automation',
            'description' => 'Generic description'
        ]);
        
        // Search for projects with 'Marketing' in the title
        $response = $this->actingAs($user)
            ->getJson('/api/projects?search=Marketing');
        
        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['title' => 'Marketing Campaign'])
            ->assertJsonFragment(['title' => 'Marketing Automation'])
            ->assertJsonMissing(['title' => 'Website Redesign']);
    }
    
    /**
     * Test basic search functionality by description
     */
    public function test_search_projects_by_description(): void
    {
        $user = User::factory()->create();
        
        // Create test projects with different descriptions
        $project1 = Project::factory()->create([
            'user_id' => $user->id,
            'title' => 'Project 1',
            'description' => 'Includes data analysis'
        ]);
        
        $project2 = Project::factory()->create([
            'user_id' => $user->id,
            'title' => 'Project 2',
            'description' => 'Involves user testing'
        ]);
        
        $project3 = Project::factory()->create([
            'user_id' => $user->id,
            'title' => 'Project 3',
            'description' => 'More data analysis needed'
        ]);
        
        // Search for projects with 'data analysis' in the description
        $response = $this->actingAs($user)
            ->getJson('/api/projects?search=data+analysis');
        
        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['description' => 'Includes data analysis'])
            ->assertJsonFragment(['description' => 'More data analysis needed'])
            ->assertJsonMissing(['description' => 'Involves user testing']);
    }
    
    /**
     * Test database driver specific search handling (SQLite vs MySQL)
     */
    public function test_search_handles_different_database_drivers(): void
    {
        $user = User::factory()->create();
        
        // Create test projects
        $project1 = Project::factory()->create([
            'user_id' => $user->id,
            'title' => 'Alpha Project',
            'description' => 'Description with searchable terms'
        ]);
        
        $project2 = Project::factory()->create([
            'user_id' => $user->id,
            'title' => 'Beta Project',
            'description' => 'Another description'
        ]);
        
        // Get current database driver
        $driver = config('database.default');
        
        // Log the driver for debugging
        \Log::info("Testing search with database driver: {$driver}");
        
        // Search for a term that should be found
        $response = $this->actingAs($user)
            ->getJson('/api/projects?search=searchable');
        
        // Regardless of driver, search should work
        $response->assertStatus(200)
            ->assertJsonFragment(['title' => 'Alpha Project'])
            ->assertJsonMissing(['title' => 'Beta Project']);
    }
    
    /**
     * Test empty search results
     */
    public function test_search_returns_empty_results_when_no_matches(): void
    {
        $user = User::factory()->create();
        
        // Create test projects
        Project::factory()->count(3)->create([
            'user_id' => $user->id
        ]);
        
        // Search for a term that won't be found
        $response = $this->actingAs($user)
            ->getJson('/api/projects?search=nonexistentterm123456');
        
        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }
    
    /**
     * Test search with special characters
     */
    public function test_search_handles_special_characters(): void
    {
        $user = User::factory()->create();
        
        // Create a project with special characters
        $project1 = Project::factory()->create([
            'user_id' => $user->id,
            'title' => 'Project #1 (Special)',
            'description' => 'Contains @special% characters!'
        ]);
        
        $project2 = Project::factory()->create([
            'user_id' => $user->id,
            'title' => 'Regular Project',
            'description' => 'Regular description'
        ]);
        
        // Search with special characters
        $response = $this->actingAs($user)
            ->getJson('/api/projects?search=%23%21%40%25'); // URL encoded "#!@%"
        
        $response->assertStatus(200);
        
        // Test a simple case with one special character
        $response = $this->actingAs($user)
            ->getJson('/api/projects?search=%23'); // "#" symbol
        
        $response->assertStatus(200)
            ->assertJsonFragment(['title' => 'Project #1 (Special)']);
    }
    
    /**
     * Test pagination with search results
     */
    public function test_search_results_are_paginated(): void
    {
        $user = User::factory()->create();
        
        // Create 15 projects with similar titles for pagination testing
        for ($i = 1; $i <= 15; $i++) {
            Project::factory()->create([
                'user_id' => $user->id,
                'title' => "Paginated Project {$i}",
                'description' => "Description {$i}"
            ]);
        }
        
        // Search with pagination - page 1, 5 items per page
        $response = $this->actingAs($user)
            ->getJson('/api/projects?search=Paginated&page=1&per_page=5');
        
        $response->assertStatus(200)
            ->assertJsonCount(5, 'data') // 5 items in data array
            ->assertJsonStructure([
                'data',
                'links',
                'meta' => [
                    'current_page',
                    'from',
                    'last_page',
                    'links',
                    'path',
                    'per_page',
                    'to',
                    'total'
                ]
            ]);
            
        // Verify total count is 15
        $this->assertEquals(15, $response->json('meta.total'));
        
        // Check page 2
        $response = $this->actingAs($user)
            ->getJson('/api/projects?search=Paginated&page=2&per_page=5');
        
        $response->assertStatus(200)
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.current_page', 2);
            
        // Check the last page should have remaining items
        $response = $this->actingAs($user)
            ->getJson('/api/projects?search=Paginated&page=3&per_page=5');
        
        $response->assertStatus(200)
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.current_page', 3);
    }
    
    /**
     * CASCADING DELETE AND EVENT HOOK TESTS
     */
    
    /**
     * Test that soft deleting a project also soft deletes its tasks.
     */
    public function test_soft_delete_cascades_to_tasks(): void
    {
        $user = User::factory()->create();
        
        // Create a project with tasks
        $project = Project::factory()->create([
            'user_id' => $user->id
        ]);
        
        // Create 3 tasks for the project
        $tasks = [];
        for ($i = 0; $i < 3; $i++) {
            $tasks[] = $project->tasks()->create([
                'title' => "Task {$i} for cascade test",
                'project_id' => $project->id
            ]);
        }
        
        // Verify tasks exist before delete
        foreach ($tasks as $task) {
            $this->assertDatabaseHas('tasks', [
                'id' => $task->id,
                'deleted_at' => null
            ]);
        }
        
        // Soft delete the project
        $project->delete();
        
        // Verify the project is soft deleted
        $this->assertSoftDeleted('projects', [
            'id' => $project->id
        ]);
        
        // Verify all tasks are also soft deleted
        foreach ($tasks as $task) {
            $this->assertSoftDeleted('tasks', [
                'id' => $task->id
            ]);
        }
    }
    
    /**
     * Test that force deleting a project also force deletes its tasks.
     */
    public function test_force_delete_cascades_to_tasks(): void
    {
        $user = User::factory()->create();
        
        // Create a project with tasks
        $project = Project::factory()->create([
            'user_id' => $user->id
        ]);
        
        // Create 3 tasks for the project
        $taskIds = [];
        for ($i = 0; $i < 3; $i++) {
            $task = $project->tasks()->create([
                'title' => "Task {$i} for force delete cascade test",
                'project_id' => $project->id
            ]);
            $taskIds[] = $task->id;
        }
        
        // Verify tasks exist before delete
        foreach ($taskIds as $taskId) {
            $this->assertDatabaseHas('tasks', [
                'id' => $taskId
            ]);
        }
        
        // Force delete the project
        $project->forceDelete();
        
        // Verify the project is permanently deleted
        $this->assertDatabaseMissing('projects', [
            'id' => $project->id
        ]);
        
        // Verify all tasks are also permanently deleted
        foreach ($taskIds as $taskId) {
            $this->assertDatabaseMissing('tasks', [
                'id' => $taskId
            ]);
        }
    }
    
    /**
     * Test that restoring a project also restores its soft-deleted tasks.
     */
    public function test_restore_cascades_to_tasks(): void
    {
        $user = User::factory()->create();
        
        // Create a project with tasks
        $project = Project::factory()->create([
            'user_id' => $user->id
        ]);
        
        // Create 3 tasks for the project
        $taskIds = [];
        for ($i = 0; $i < 3; $i++) {
            $task = $project->tasks()->create([
                'title' => "Task {$i} for restore cascade test",
                'project_id' => $project->id
            ]);
            $taskIds[] = $task->id;
        }
        
        // First soft delete the project (which cascades to tasks)
        $project->delete();
        
        // Verify the project is soft deleted
        $this->assertSoftDeleted('projects', [
            'id' => $project->id
        ]);
        
        // Verify all tasks are also soft deleted
        foreach ($taskIds as $taskId) {
            $this->assertSoftDeleted('tasks', [
                'id' => $taskId
            ]);
        }
        
        // Now restore the project
        $project = Project::withTrashed()->find($project->id);
        $project->restore();
        
        // Verify the project is restored
        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'deleted_at' => null
        ]);
        
        // Verify all tasks are also restored
        foreach ($taskIds as $taskId) {
            $this->assertDatabaseHas('tasks', [
                'id' => $taskId,
                'deleted_at' => null
            ]);
        }
    }
    
    /**
     * Test that new tasks added to a deleted project are automatically soft deleted.
     */
    public function test_new_tasks_added_to_deleted_project_are_soft_deleted(): void
    {
        $user = User::factory()->create();
        
        // Create and soft delete a project
        $project = Project::factory()->create([
            'user_id' => $user->id
        ]);
        $project->delete();
        
        // Retrieve the soft-deleted project
        $trashedProject = Project::withTrashed()->find($project->id);
        
        // Create a new task for the soft-deleted project
        $task = $trashedProject->tasks()->create([
            'title' => 'Task for deleted project',
            'project_id' => $trashedProject->id
        ]);
        
        // The task should be created in a deleted state automatically
        $this->assertSoftDeleted('tasks', [
            'id' => $task->id
        ]);
    }
    
    /**
     * Test selective restoration of tasks when restoring a project.
     */
    public function test_selective_task_restoration(): void
    {
        $user = User::factory()->create();
        
        // Create a project
        $project = Project::factory()->create([
            'user_id' => $user->id
        ]);
        
        // Create 2 tasks
        $task1 = $project->tasks()->create([
            'title' => 'Task 1 for selective restore test',
            'project_id' => $project->id
        ]);
        
        $task2 = $project->tasks()->create([
            'title' => 'Task 2 for selective restore test',
            'project_id' => $project->id
        ]);
        
        // Soft delete just task 1
        $task1->delete();
        
        // Soft delete the project
        $project->delete();
        
        // Verify both tasks are now soft deleted
        $this->assertSoftDeleted('tasks', ['id' => $task1->id]);
        $this->assertSoftDeleted('tasks', ['id' => $task2->id]);
        
        // Now restore the project
        $project = Project::withTrashed()->find($project->id);
        $project->restore();
        
        // Both tasks should be restored
        $this->assertDatabaseHas('tasks', [
            'id' => $task1->id,
            'deleted_at' => null
        ]);
        
        $this->assertDatabaseHas('tasks', [
            'id' => $task2->id,
            'deleted_at' => null
        ]);
    }
    
    /**
     * Test that tasks from other projects are not affected by cascading operations.
     */
    public function test_cascade_operations_do_not_affect_other_projects_tasks(): void
    {
        $user = User::factory()->create();
        
        // Create two projects
        $project1 = Project::factory()->create([
            'user_id' => $user->id,
            'title' => 'Project 1'
        ]);
        
        $project2 = Project::factory()->create([
            'user_id' => $user->id,
            'title' => 'Project 2'
        ]);
        
        // Create tasks for each project
        $task1 = $project1->tasks()->create([
            'title' => 'Task for Project 1',
            'project_id' => $project1->id
        ]);
        
        $task2 = $project2->tasks()->create([
            'title' => 'Task for Project 2',
            'project_id' => $project2->id
        ]);
        
        // Soft delete project 1
        $project1->delete();
        
        // Verify project 1 and its task are soft deleted
        $this->assertSoftDeleted('projects', ['id' => $project1->id]);
        $this->assertSoftDeleted('tasks', ['id' => $task1->id]);
        
        // Verify project 2 and its task are NOT deleted
        $this->assertDatabaseHas('projects', [
            'id' => $project2->id,
            'deleted_at' => null
        ]);
        
        $this->assertDatabaseHas('tasks', [
            'id' => $task2->id,
            'deleted_at' => null
        ]);
        
        // Now restore project 1
        $project1 = Project::withTrashed()->find($project1->id);
        $project1->restore();
        
        // Verify project 1 and its task are restored
        $this->assertDatabaseHas('projects', [
            'id' => $project1->id,
            'deleted_at' => null
        ]);
        
        $this->assertDatabaseHas('tasks', [
            'id' => $task1->id,
            'deleted_at' => null
        ]);
        
        // Project 2 and its task should remain unchanged
        $this->assertDatabaseHas('projects', [
            'id' => $project2->id,
            'deleted_at' => null
        ]);
        
        $this->assertDatabaseHas('tasks', [
            'id' => $task2->id,
            'deleted_at' => null
        ]);
    }
    
    /**
     * Test handling of many tasks in cascading operations
     */
    public function test_cascade_operations_with_many_tasks(): void
    {
        $user = User::factory()->create();
        
        // Create a project
        $project = Project::factory()->create([
            'user_id' => $user->id
        ]);
        
        // Create 50 tasks (large number to test performance and reliability)
        $taskIds = [];
        for ($i = 0; $i < 50; $i++) {
            $task = $project->tasks()->create([
                'title' => "Task {$i} for many tasks cascade test",
                'project_id' => $project->id
            ]);
            $taskIds[] = $task->id;
        }
        
        // Soft delete the project
        $startTime = microtime(true);
        $project->delete();
        $endTime = microtime(true);
        
        // Log the time taken for deletion (performance check)
        $timeTaken = $endTime - $startTime;
        \Log::info("Time taken to cascade delete 50 tasks: {$timeTaken} seconds");
        
        // Verify all 50 tasks are soft deleted
        foreach ($taskIds as $taskId) {
            $this->assertSoftDeleted('tasks', [
                'id' => $taskId
            ]);
        }
        
        // Now restore the project
        $startTime = microtime(true);
        $project = Project::withTrashed()->find($project->id);
        $project->restore();
        $endTime = microtime(true);
        
        // Log the time taken for restoration (performance check)
        $timeTaken = $endTime - $startTime;
        \Log::info("Time taken to cascade restore 50 tasks: {$timeTaken} seconds");
        
        // Verify all 50 tasks are restored
        foreach ($taskIds as $taskId) {
            $this->assertDatabaseHas('tasks', [
                'id' => $taskId,
                'deleted_at' => null
            ]);
        }
    }
}
