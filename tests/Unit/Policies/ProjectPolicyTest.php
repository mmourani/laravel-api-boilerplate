<?php

namespace Tests\Unit\Policies;

use App\Models\Project;
use App\Models\User;
use App\Policies\ProjectPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectPolicyTest extends TestCase
{
    use RefreshDatabase;

    private ProjectPolicy $policy;
    private User $owner;
    private User $nonOwner;
    private Project $project;

    /**
     * Set up the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create a policy instance
        $this->policy = new ProjectPolicy();

        // Create users for testing
        $this->owner = User::factory()->create();
        $this->nonOwner = User::factory()->create();

        // Create a project owned by the owner user
        $this->project = Project::factory()->create([
            'user_id' => $this->owner->id,
        ]);
    }

    /**
     * Test view authorization for project owner.
     */
    public function test_owner_can_view_project(): void
    {
        $this->assertTrue(
            $this->policy->view($this->owner, $this->project)
        );
    }

    /**
     * Test view authorization for non-owner.
     */
    public function test_non_owner_cannot_view_project(): void
    {
        $this->assertFalse(
            $this->policy->view($this->nonOwner, $this->project)
        );
    }

    /**
     * Test update authorization for project owner.
     */
    public function test_owner_can_update_project(): void
    {
        $this->assertTrue(
            $this->policy->update($this->owner, $this->project)
        );
    }

    /**
     * Test update authorization for non-owner.
     */
    public function test_non_owner_cannot_update_project(): void
    {
        $this->assertFalse(
            $this->policy->update($this->nonOwner, $this->project)
        );
    }

    /**
     * Test delete authorization for project owner.
     */
    public function test_owner_can_delete_project(): void
    {
        $this->assertTrue(
            $this->policy->delete($this->owner, $this->project)
        );
    }

    /**
     * Test delete authorization for non-owner.
     */
    public function test_non_owner_cannot_delete_project(): void
    {
        $this->assertFalse(
            $this->policy->delete($this->nonOwner, $this->project)
        );
    }

    /**
     * Test that all policy methods consistently check ownership.
     */
    public function test_all_policy_methods_are_consistent(): void
    {
        // For the owner user, all policy methods should return true
        $this->assertTrue($this->policy->view($this->owner, $this->project));
        $this->assertTrue($this->policy->update($this->owner, $this->project));
        $this->assertTrue($this->policy->delete($this->owner, $this->project));

        // For the non-owner user, all policy methods should return false
        $this->assertFalse($this->policy->view($this->nonOwner, $this->project));
        $this->assertFalse($this->policy->update($this->nonOwner, $this->project));
        $this->assertFalse($this->policy->delete($this->nonOwner, $this->project));
    }
}

