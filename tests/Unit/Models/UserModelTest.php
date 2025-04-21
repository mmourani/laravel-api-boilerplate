<?php

namespace Tests\Unit\Models;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_has_many_projects(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();

        $this->assertTrue($user->projects->contains($project));
        $this->assertInstanceOf(Project::class, $user->projects->first());
    }
}