<?php

namespace Tests\Unit\Policies;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Policies\TaskPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery;

class TaskPolicyTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private User $nonOwner;
    private Project $project;
    private Task $task;
    private TaskPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        // Create users
        $this->owner = User::factory()->create();
        $this->nonOwner = User::factory()->create();

        // Create a project owned by the owner
        $this->project = Project::factory()->create([
            'user_id' => $this->owner->id
        ]);

        // Create a task in that project
        $this->task = Task::factory()->create([
            'project_id' => $this->project->id,
            'title' => 'Test Task'
        ]);

        // Initialize the policy
        $this->policy = new TaskPolicy();
    }

    /**
     * Test that a project owner can view a task in their project.
     */
    public function test_owner_can_view_task(): void
    {
        $this->assertTrue(
            $this->policy->view($this->owner, $this->task),
            'Project owner should be able to view tasks in their project'
        );
    }

    /**
     * Test that a non-owner cannot view another user's project tasks.
     */
    public function test_non_owner_cannot_view_task(): void
    {
        $this->assertFalse(
            $this->policy->view($this->nonOwner, $this->task),
            'Non-owner should not be able to view tasks in another user\'s project'
        );
    }

    /**
     * Test that a project owner can create a task in their project.
     */
    public function test_owner_can_create_task(): void
    {
        $this->assertTrue(
            $this->policy->create($this->owner, $this->project->id),
            'Project owner should be able to create tasks in their project'
        );
    }

    /**
     * Test that a non-owner cannot create a task in another user's project.
     */
    public function test_non_owner_cannot_create_task(): void
    {
        $this->assertFalse(
            $this->policy->create($this->nonOwner, $this->project->id),
            'Non-owner should not be able to create tasks in another user\'s project'
        );
    }

    /**
     * Test that a project owner can update a task in their project.
     */
    public function test_owner_can_update_task(): void
    {
        $this->assertTrue(
            $this->policy->update($this->owner, $this->task),
            'Project owner should be able to update tasks in their project'
        );
    }

    /**
     * Test that a non-owner cannot update a task in another user's project.
     */
    public function test_non_owner_cannot_update_task(): void
    {
        $this->assertFalse(
            $this->policy->update($this->nonOwner, $this->task),
            'Non-owner should not be able to update tasks in another user\'s project'
        );
    }

    /**
     * Test that a project owner can delete a task in their project.
     */
    public function test_owner_can_delete_task(): void
    {
        $this->assertTrue(
            $this->policy->delete($this->owner, $this->task),
            'Project owner should be able to delete tasks in their project'
        );
    }

    /**
     * Test that a non-owner cannot delete a task in another user's project.
     */
    public function test_non_owner_cannot_delete_task(): void
    {
        $this->assertFalse(
            $this->policy->delete($this->nonOwner, $this->task),
            'Non-owner should not be able to delete tasks in another user\'s project'
        );
    }

    /**
     * Test that a project owner can restore a deleted task in their project.
     */
    public function test_owner_can_restore_task(): void
    {
        // First delete the task
        $this->task->delete();

        $this->assertTrue(
            $this->policy->restore($this->owner, $this->task),
            'Project owner should be able to restore tasks in their project'
        );
    }

    /**
     * Test that a non-owner cannot restore a deleted task in another user's project.
     */
    public function test_non_owner_cannot_restore_task(): void
    {
        // First delete the task
        $this->task->delete();

        $this->assertFalse(
            $this->policy->restore($this->nonOwner, $this->task),
            'Non-owner should not be able to restore tasks in another user\'s project'
        );
    }

    /**
     * Test that project owner can force delete a task in their project.
     */
    public function test_owner_can_force_delete_task(): void
    {
        $this->assertTrue(
            $this->policy->forceDelete($this->owner, $this->task),
            'Project owner should be able to force delete tasks in their project'
        );
    }

    /**
     * Test that a non-owner cannot force delete a task in another user's project.
     */
    public function test_non_owner_cannot_force_delete_task(): void
    {
        $this->assertFalse(
            $this->policy->forceDelete($this->nonOwner, $this->task),
            'Non-owner should not be able to force delete tasks in another user\'s project'
        );
    }

    /**
     * Test policy consistency by verifying all permission methods follow the same owner logic.
     */
    public function test_all_policy_methods_are_consistent(): void
    {
        $methods = ['view', 'update', 'delete', 'restore', 'forceDelete'];

        foreach ($methods as $method) {
            // Owner should be allowed for all methods
            $this->assertTrue(
                $this->policy->$method($this->owner, $this->task),
                "Owner should be allowed to {$method} tasks"
            );

            // Non-owner should be denied for all methods
            $this->assertFalse(
                $this->policy->$method($this->nonOwner, $this->task),
                "Non-owner should not be allowed to {$method} tasks"
            );
        }
    }

    /**
     * Test task policy behavior when the parent project is deleted.
     */
    public function test_task_policy_with_deleted_project(): void
    {
        // Delete the project
        $this->project->delete();

        // Even though the task's project is deleted, the owner should still have permission
        // as long as the task can be retrieved with the project relationship
        $this->assertTrue(
            $this->policy->view($this->owner, $this->task),
            'Owner should still have permission for tasks even if project is soft-deleted'
        );

        // Non-owner should still be denied
        $this->assertFalse(
            $this->policy->view($this->nonOwner, $this->task),
            'Non-owner should still be denied access to tasks in deleted projects'
        );
    }

    /**
     * Test policy validation with non-existent project.
     */
    public function test_create_task_with_non_existent_project(): void
    {
        // Try to create task with non-existent project ID
        $nonExistentProjectId = 99999;

        // Both owner and non-owner should be denied
        $this->assertFalse(
            $this->policy->create($this->owner, $nonExistentProjectId),
            'Users should not be able to create tasks for non-existent projects'
        );

        $this->assertFalse(
            $this->policy->create($this->nonOwner, $nonExistentProjectId),
            'Users should not be able to create tasks for non-existent projects'
        );
    }

    /**
     * Test task policy behavior when a task is orphaned (has no project relation).
     */
    public function test_task_policy_with_orphaned_task(): void
    {
        // Create a mock task with no project relation instead of trying to save to DB
        // This avoids the NOT NULL constraint on project_id in the database
        $orphanedTask = Mockery::mock(Task::class);
        $orphanedTask->shouldReceive('getAttribute')->with('id')->andReturn(999);
        $orphanedTask->shouldReceive('getAttribute')->with('project_id')->andReturn(null);
        
        // No one should have permission on orphaned tasks
        $this->assertFalse(
            $this->policy->view($this->owner, $orphanedTask),
            'No one should have permission for orphaned tasks'
        );

        $this->assertFalse(
            $this->policy->view($this->nonOwner, $orphanedTask),
            'No one should have permission for orphaned tasks'
        );
    }

    /**
     * Test task policy behavior when project ownership changes.
     */
    public function test_task_policy_with_project_ownership_change(): void
    {
        // Original owner should have permission
        $this->assertTrue(
            $this->policy->view($this->owner, $this->task),
            'Original owner should have permission for tasks in their project'
        );

        // Change project ownership
        $this->project->user_id = $this->nonOwner->id;
        $this->project->save();

        // After ownership change, original owner should lose permission
        $this->assertFalse(
            $this->policy->view($this->owner, $this->task),
            'Original owner should lose permission after project ownership changes'
        );

        // New owner should gain permission
        $this->assertTrue(
            $this->policy->view($this->nonOwner, $this->task),
            'New owner should gain permission after project ownership changes'
        );
    }
    
    /**
     * Clean up mocks after each test
     */
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}

