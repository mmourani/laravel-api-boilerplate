<?php

namespace Tests\Unit\Models;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that a project belongs to a user.
     */
    public function test_project_belongs_to_user(): void
    {
        // Create a project instance
        $project = new Project();
        
        // Check that the relationship method exists and returns the correct type
        $this->assertInstanceOf(BelongsTo::class, $project->user());
        
        // Test with actual records
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);
        
        // Check that the project is associated with the correct user
        $this->assertInstanceOf(User::class, $project->user);
        $this->assertEquals($user->id, $project->user->id);
    }

    /**
     * Test that a project has many tasks.
     */
    public function test_project_has_many_tasks(): void
    {
        // Create a project instance
        $project = new Project();
        
        // Check that the relationship method exists and returns the correct type
        $this->assertInstanceOf(HasMany::class, $project->tasks());
        
        // Test with actual records
        $project = Project::factory()->create();
        $this->assertInstanceOf(Collection::class, $project->tasks);
        $this->assertCount(0, $project->tasks);
        
        // Create tasks for the project
        Task::factory()->count(3)->create(['project_id' => $project->id]);
        
        // Refresh the project instance to get the related tasks
        $project->refresh();
        
        // Check that tasks are associated correctly
        $this->assertCount(3, $project->tasks);
        $this->assertInstanceOf(Task::class, $project->tasks->first());
    }

    /**
     * Test fillable attributes.
     */
    public function test_fillable_attributes(): void
    {
        $project = new Project();
        
        // Check that the fillable array contains expected attributes
        $this->assertEquals([
            'title',
            'description',
        ], $project->getFillable());
        
        // Test mass assignment with fillable attributes
        $projectData = [
            'title' => 'Test Project',
            'description' => 'This is a test project',
        ];
        
        $project = new Project($projectData);
        
        $this->assertEquals('Test Project', $project->title);
        $this->assertEquals('This is a test project', $project->description);
    }

    /**
     * Test project creation with factory.
     */
    public function test_project_creation_with_factory(): void
    {
        // Create a project using the factory
        $project = Project::factory()->create();
        
        // Check that the project was created with all required attributes
        $this->assertNotNull($project->id);
        $this->assertNotNull($project->title);
        $this->assertNotNull($project->description);
        $this->assertNotNull($project->user_id);
        
        // Verify the user was also created
        $this->assertDatabaseHas('users', [
            'id' => $project->user_id,
        ]);
        
        // Check that relationships were properly established
        $this->assertInstanceOf(User::class, $project->user);
    }

    /**
     * Test cascading deletes for tasks.
     */
    public function test_cascading_deletes_for_tasks(): void
    {
        // Create a project with tasks
        $project = Project::factory()->create();
        $tasks = Task::factory()->count(3)->create(['project_id' => $project->id]);
        
        // Verify tasks exist in the database
        foreach ($tasks as $task) {
            $this->assertDatabaseHas('tasks', [
                'id' => $task->id,
                'project_id' => $project->id,
            ]);
        }
        
        // Delete the project
        $projectId = $project->id;
        $project->delete();
        
        // Verify the project was deleted
        $this->assertDatabaseMissing('projects', [
            'id' => $projectId,
        ]);
        
        // Verify all associated tasks were deleted (or have null project_id if soft deletes are used)
        // Note: This test assumes ON DELETE CASCADE is set up in the database
        // If using soft deletes or other deletion strategies, adjust this test accordingly
        
        foreach ($tasks as $task) {
            $this->assertDatabaseMissing('tasks', [
                'id' => $task->id,
                'project_id' => $projectId,
            ]);
        }
    }
}

