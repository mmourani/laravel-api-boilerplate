<?php

namespace Tests\Unit\Policies;

use App\Models\Project;
use App\Models\User;
use App\Policies\ProjectPolicy;
use Tests\TestCase;

class ProjectPolicyTest extends TestCase
{
    protected ProjectPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new ProjectPolicy();
    }

    public function test_user_can_view_own_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->make();

        $this->assertTrue($this->policy->view($user, $project));
    }

    public function test_user_cannot_view_others_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(); // owned by another user

        $this->assertFalse($this->policy->view($user, $project));
    }

    public function test_user_can_update_own_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->make();

        $this->assertTrue($this->policy->update($user, $project));
    }

    public function test_user_cannot_update_others_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        $this->assertFalse($this->policy->update($user, $project));
    }

    public function test_user_can_delete_own_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->make();

        $this->assertTrue($this->policy->delete($user, $project));
    }

    public function test_user_cannot_delete_others_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        $this->assertFalse($this->policy->delete($user, $project));
    }

    public function test_user_can_restore_own_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->make();

        $this->assertTrue($this->policy->restore($user, $project));
    }

    public function test_user_cannot_restore_others_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        $this->assertFalse($this->policy->restore($user, $project));
    }
}