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
     * Test cascading soft deletes for tasks.
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
        
        // Soft delete the project
        $projectId = $project->id;
        $project->delete();
        
        // Verify the project was soft deleted
        $this->assertSoftDeleted('projects', [
            'id' => $projectId,
        ]);
        
        // Verify all associated tasks were soft deleted
        foreach ($tasks as $task) {
            $this->assertSoftDeleted('tasks', [
                'id' => $task->id,
                'project_id' => $projectId,
            ]);
        }
    }
    
    /**
     * Test project restoration with cascading restoration of tasks.
     */
    public function test_project_restoration(): void
    {
        // Create a project with tasks
        $project = Project::factory()->create();
        $tasks = Task::factory()->count(3)->create(['project_id' => $project->id]);
        
        // Store IDs for later verification
        $projectId = $project->id;
        $taskIds = $tasks->pluck('id')->toArray();
        
        // Soft delete the project
        $project->delete();
        
        // Verify the project and tasks were soft deleted
        $this->assertSoftDeleted('projects', ['id' => $projectId]);
        foreach ($taskIds as $taskId) {
            $this->assertSoftDeleted('tasks', ['id' => $taskId]);
        }
        
        // Restore the project
        $project = Project::withTrashed()->find($projectId);
        $project->restore();
        
        // Verify the project was restored
        $this->assertDatabaseHas('projects', [
            'id' => $projectId,
            'deleted_at' => null,
        ]);
        
        // Verify all tasks were also restored
        foreach ($taskIds as $taskId) {
            $this->assertDatabaseHas('tasks', [
                'id' => $taskId,
                'deleted_at' => null,
            ]);
        }
    }
    
    /**
     * Test that deleted_at is properly cast to a datetime.
     */
    public function test_deleted_at_is_cast_properly(): void
    {
        // Create and delete a project
        $project = Project::factory()->create();
        $project->delete();
        
        // Retrieve the deleted project
        $deletedProject = Project::withTrashed()->find($project->id);
        
        // Assert deleted_at is a Carbon instance
        $this->assertInstanceOf('Carbon\Carbon', $deletedProject->deleted_at);
    }
}

