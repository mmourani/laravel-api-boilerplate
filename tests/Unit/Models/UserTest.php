<?php

namespace Tests\Unit\Models;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that a user has many projects.
     */
    public function test_user_has_many_projects(): void
    {
        // Create a user instance
        $user = new User();
        
        // Check that the relationship method exists and returns the correct type
        $this->assertInstanceOf(HasMany::class, $user->projects());
        
        // Test with actual records
        $user = User::factory()->create();
        $this->assertInstanceOf(Collection::class, $user->projects);
        $this->assertCount(0, $user->projects);
        
        // Create projects for the user
        Project::factory()->count(3)->create(['user_id' => $user->id]);
        
        // Refresh the user instance to get the related projects
        $user->refresh();
        
        // Check that projects are associated correctly
        $this->assertCount(3, $user->projects);
        $this->assertInstanceOf(Project::class, $user->projects->first());
    }

    /**
     * Test that attributes are properly cast.
     */
    public function test_attributes_are_cast_correctly(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        
        $this->assertIsObject($user->email_verified_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $user->email_verified_at);
        
        // Test password is hashed
        $plainPassword = 'password123';
        $user = User::factory()->create([
            'password' => $plainPassword,
        ]);
        
        // Password should be hashed and not equal to the plain text password
        $this->assertNotSame($plainPassword, $user->password);
        // Should be a bcrypt hash (starts with $2y$)
        $this->assertStringStartsWith('$2y$', $user->password);
    }

    /**
     * Test hidden attributes.
     */
    public function test_hidden_attributes(): void
    {
        $user = User::factory()->create([
            'password' => 'secret-password',
            'remember_token' => 'test-token',
        ]);
        
        $userArray = $user->toArray();
        
        // Hidden attributes should not be visible in the array
        $this->assertArrayNotHasKey('password', $userArray);
        $this->assertArrayNotHasKey('remember_token', $userArray);
    }

    /**
     * Test fillable attributes.
     */
    public function test_fillable_attributes(): void
    {
        $user = new User();
        
        // Check that the fillable array contains expected attributes
        $this->assertEquals([
            'name',
            'email',
            'password',
        ], $user->getFillable());
        
        // Test mass assignment with fillable attributes
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password',
        ];
        
        // Create user without automagic behavior
        $user = new class($userData) extends User {
            // Override the boot method to prevent password hashing for this test
            protected function initializeTraits() {}
        };
        
        $this->assertEquals('John Doe', $user->name);
        $this->assertEquals('john@example.com', $user->email);
        $this->assertEquals('password', $user->password);
        
        // Alternatively, test the underlying mechanism instead of direct property comparison
        $normalUser = new User();
        $normalUser->fill($userData);
        
        $this->assertEquals('John Doe', $normalUser->name);
        $this->assertEquals('john@example.com', $normalUser->email);
        // Password is hashed automatically, so just verify it's not the original value
        $this->assertNotSame('password', $normalUser->password);
    }
}

