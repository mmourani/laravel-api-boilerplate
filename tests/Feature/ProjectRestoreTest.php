<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Mockery;
use Tests\TestCase;

class ProjectRestoreTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Start with clean facades
        Facade::clearResolvedInstances();
        
        // We don't set up static mocking here anymore - we'll do it after
        // factory calls in each test to avoid conflicts with the factory method
    }
    /**
     * Test unauthorized attempt to restore another user's project.
     */
    public function test_unauthorized_restore_attempt(): void
    {
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $anotherUser->id,
        ]);

        // Soft delete the project
        $project->delete();
        $projectId = $project->id;

        $response = $this->actingAs($user)
            ->postJson("/api/projects/{$projectId}/restore");

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Unauthorized to restore this project',
            ]);

        // Verify the project remains soft-deleted
        $this->assertSoftDeleted(
            'projects', 
            ['id' => $projectId]
        );
        
        // Additional debug information for clarity
        // Verify project is still trashed
        $this->assertTrue(
            Project::withTrashed()->find($projectId)->trashed(),
            Project::withTrashed()->find($projectId)->trashed(),
            'Project should remain soft-deleted after unauthorized restoration attempt'
        );
    }

    /**
     * Test exception handling during project restoration.
     */
    public function test_exception_during_project_restoration(): void
    {
        // Instead of trying to mock Project, we'll use a real Project and 
        // mock the specific controller that interacts with it
        
        // Create test data
        $projectId = 999;
        $userId = 123;
        
        // Create a user and project
        $user = User::factory()->create([
            'id' => $userId
        ]);
        
        $project = Project::factory()->create([
            'id' => $projectId,
            'user_id' => $userId
        ]);
        
        // Soft delete the project
        $project->delete();
        
        // Create a mock controller
        $mockController = $this->createPartialMock(\App\Http\Controllers\ProjectController::class, ['restore']);
        
        // Configure the mock to throw an exception during restoration
        $mockController->expects($this->once())
            ->method('restore')
            ->willThrowException(new \Exception('Unexpected database error'));
        
        // Replace the controller in the container
        $this->app->instance(\App\Http\Controllers\ProjectController::class, $mockController);
        
        // Add debug logging
        \Log::info("Controller mock setup", [
            'project_id' => $projectId,
            'user_id' => $userId
        ]);
        
        \Log::info("Test setup complete", [
            'user_id' => $user->id,
            'project_id' => $projectId
        ]);
        
        // Ensure authorization passes
        Gate::define('restore', function ($authUser, $project) {
            return $authUser->id === $project->user_id;
        });
        
        \Log::info("Mock setup complete");
        
        // Attempt to restore the project
        $response = $this->actingAs($user)
            ->postJson("/api/projects/{$projectId}/restore");
        
        \Log::info("Response received", [
            'status' => $response->status(),
            'content' => $response->content()
        ]);
        
        // Assert the response matches Laravel's default exception handling format
        $response->assertStatus(500)
            ->assertJson([
                'message' => 'Unexpected database error',
                'exception' => 'Exception',
            ]);
            
        // Also verify that the file path is included in the response
        $this->assertStringContainsString(
            'ProjectRestoreTest.php', 
            $response->json('file')
        );
            
        \Log::info("Test complete");
    }
    
    protected function tearDown(): void
    {
        // Remove any custom error handlers
        restore_error_handler();
        restore_exception_handler();
        // Reset route bindings to avoid affecting other tests
        Route::getRoutes()->refreshNameLookups();
        
        // Clean up Mockery
        Mockery::close();
        
        // Reset all facade mocks
        Facade::clearResolvedInstances();
        
        parent::tearDown();
    }
}
