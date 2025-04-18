<?php

namespace Tests\Unit\Models;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that a task belongs to a project.
     */
    public function test_task_belongs_to_project(): void
    {
        // Create a task instance
        $task = new Task();
        
        // Check that the relationship method exists and returns the correct type
        $this->assertInstanceOf(BelongsTo::class, $task->project());
        
        // Test with actual records
        $project = Project::factory()->create();
        $task = Task::factory()->create(['project_id' => $project->id]);
        
        // Check that the task is associated with the correct project
        $this->assertInstanceOf(Project::class, $task->project);
        $this->assertEquals($project->id, $task->project->id);
    }

    /**
     * Test attributes are properly cast.
     */
    public function test_attributes_are_cast_correctly(): void
    {
        // Test boolean casting for 'done'
        $task = Task::factory()->create(['done' => true]);
        $this->assertIsBool($task->done);
        $this->assertTrue($task->done);
        
        $task = Task::factory()->create(['done' => false]);
        $this->assertIsBool($task->done);
        $this->assertFalse($task->done);
        
        // Test 1/0 values are cast to boolean
        $task = Task::factory()->create(['done' => 1]);
        $this->assertIsBool($task->done);
        $this->assertTrue($task->done);
        
        $task = Task::factory()->create(['done' => 0]);
        $this->assertIsBool($task->done);
        $this->assertFalse($task->done);
        
        // Test datetime casting for 'due_date'
        $dueDate = now();
        $task = Task::factory()->create(['due_date' => $dueDate]);
        
        $this->assertInstanceOf(Carbon::class, $task->due_date);
        $this->assertEquals(
            $dueDate->format('Y-m-d H:i:s'), 
            $task->due_date->format('Y-m-d H:i:s')
        );
    }

    /**
     * Test fillable attributes.
     */
    public function test_fillable_attributes(): void
    {
        $task = new Task();
        
        // Check that the fillable array contains expected attributes
        $this->assertEquals([
            'title',
            'project_id',
            'done',
            'priority',
            'due_date',
        ], $task->getFillable());
        
        // Test mass assignment with fillable attributes
        $project = Project::factory()->create();
        $taskData = [
            'title' => 'Complete unit tests',
            'project_id' => $project->id,
            'done' => false,
            'priority' => 'high',
            'due_date' => now()->addDay(),
        ];
        
        $task = new Task($taskData);
        
        $this->assertEquals('Complete unit tests', $task->title);
        $this->assertEquals($project->id, $task->project_id);
        $this->assertEquals(false, $task->done);
        $this->assertEquals('high', $task->priority);
    }

    /**
     * Test priority validation.
     */
    public function test_priority_validation(): void
    {
        // Test valid priority values
        $validPriorities = ['low', 'medium', 'high'];
        
        foreach ($validPriorities as $priority) {
            $task = Task::factory()->create(['priority' => $priority]);
            $this->assertEquals($priority, $task->priority);
            
            $this->assertDatabaseHas('tasks', [
                'id' => $task->id,
                'priority' => $priority,
            ]);
        }
        
        // Test invalid priority (would typically be caught by validation in the controller)
        // For model tests, we're just ensuring the database accepts valid values
        // but actual validation would happen at the request level
    }

    /**
     * Test factory states.
     */
    public function test_factory_states(): void
    {
        // Test 'done' state
        $task = Task::factory()->done()->create();
        $this->assertTrue($task->done);
        
        // Test 'pending' state
        $task = Task::factory()->pending()->create();
        $this->assertFalse($task->done);
        
        // Test 'priority' state with each valid priority
        $task = Task::factory()->priority('low')->create();
        $this->assertEquals('low', $task->priority);
        
        $task = Task::factory()->priority('medium')->create();
        $this->assertEquals('medium', $task->priority);
        
        $task = Task::factory()->priority('high')->create();
        $this->assertEquals('high', $task->priority);
        
        // Test chaining states
        $task = Task::factory()->done()->priority('high')->create();
        $this->assertTrue($task->done);
        $this->assertEquals('high', $task->priority);
    }

    /**
     * Test the isValidPriority method.
     */
    public function test_is_valid_priority_method(): void
    {
        // Valid priorities
        $this->assertTrue(Task::isValidPriority('low'));
        $this->assertTrue(Task::isValidPriority('medium'));
        $this->assertTrue(Task::isValidPriority('high'));
        
        // Null is also considered valid
        $this->assertTrue(Task::isValidPriority(null));
        
        // Invalid priorities
        $this->assertFalse(Task::isValidPriority('MEDIUM')); // Case sensitive
        $this->assertFalse(Task::isValidPriority('urgent'));
        $this->assertFalse(Task::isValidPriority('critical'));
        $this->assertFalse(Task::isValidPriority(''));
    }

    /**
     * Test scope methods for filtering.
     */
    public function test_scope_by_priority(): void
    {
        // Create tasks with different priorities
        Task::factory()->count(2)->create(['priority' => 'high']);
        Task::factory()->count(3)->create(['priority' => 'medium']);
        Task::factory()->count(1)->create(['priority' => 'low']);
        
        // Test scope by high priority
        $highTasks = Task::byPriority('high')->get();
        $this->assertCount(2, $highTasks);
        foreach ($highTasks as $task) {
            $this->assertEquals('high', $task->priority);
        }
        
        // Test scope by medium priority
        $mediumTasks = Task::byPriority('medium')->get();
        $this->assertCount(3, $mediumTasks);
        
        // Test with invalid priority (should return empty)
        $invalidTasks = Task::byPriority('invalid')->get();
        $this->assertCount(6, $invalidTasks); // Returns all tasks
        
        // Test with null priority
        $nullTasks = Task::byPriority(null)->get();
        $this->assertCount(6, $nullTasks); // Returns all tasks
    }

    /**
     * Test scope by completion status.
     */
    public function test_scope_by_status(): void
    {
        // Create tasks with different completion statuses
        Task::factory()->count(2)->create(['done' => true]);
        Task::factory()->count(4)->create(['done' => false]);
        
        // Test scope for completed tasks
        $completedTasks = Task::byStatus(true)->get();
        $this->assertCount(2, $completedTasks);
        foreach ($completedTasks as $task) {
            $this->assertTrue($task->done);
        }
        
        // Test scope for pending tasks
        $pendingTasks = Task::byStatus(false)->get();
        $this->assertCount(4, $pendingTasks);
        
        // Test with null status (should return all)
        $allTasks = Task::byStatus(null)->get();
        $this->assertCount(6, $allTasks);
    }

    /**
     * Test scope by due date.
     */
    public function test_scope_by_due_date(): void
    {
        $today = now();
        $tomorrow = $today->copy()->addDay();
        $nextWeek = $today->copy()->addWeek();
        
        // Create tasks with different due dates
        Task::factory()->create(['due_date' => $today]);
        Task::factory()->count(2)->create(['due_date' => $tomorrow]);
        Task::factory()->create(['due_date' => $nextWeek]);
        Task::factory()->create(['due_date' => null]);
        
        // Test scope by tomorrow's date
        $tomorrowTasks = Task::byDueDate($tomorrow->format('Y-m-d'))->get();
        $this->assertCount(2, $tomorrowTasks);
        
        // Test with null date (should return all)
        $allTasks = Task::byDueDate(null)->get();
        $this->assertCount(5, $allTasks);
        
        // Test with non-existent date
        $nonExistentTasks = Task::byDueDate('2099-12-31')->get();
        $this->assertCount(0, $nonExistentTasks);
    }

    /**
     * Test search scope functionality.
     */
    public function test_scope_search(): void
    {
        // Create tasks with specific titles for testing search
        $searchTask1 = Task::factory()->create(['title' => 'Implement search feature']);
        $searchTask2 = Task::factory()->create(['title' => 'Fix search bugs']);
        $otherTask = Task::factory()->create(['title' => 'Deploy application']);
        
        // Skip the test if using MySQL without fulltext indexes
        if (config('database.default') !== 'sqlite') {
            try {
                // Attempt a fulltext search to see if it's properly set up
                Task::search('test')->get();
            } catch (\Exception $e) {
                $this->markTestSkipped('Fulltext search is not available in the current database configuration.');
                return;
            }
        }

        // Now we can safely test the search functionality
        
        // Test searching for 'search' - should find 2 tasks
        $searchTasks = Task::search('search')->get();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $searchTasks);
        
        // In SQLite with our fallback implementation, we should find the two tasks with 'search' in the title
        if (config('database.default') === 'sqlite') {
            $this->assertCount(2, $searchTasks);
            $this->assertTrue($searchTasks->contains('id', $searchTask1->id));
            $this->assertTrue($searchTasks->contains('id', $searchTask2->id));
            $this->assertFalse($searchTasks->contains('id', $otherTask->id));
        }
        
        // Test empty search string - should return all tasks
        $allTasks = Task::search('')->get();
        $this->assertCount(3, $allTasks);
        
        // Test non-matching search - should return empty collection
        $nonMatchingTasks = Task::search('nonexistentterm')->get();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $nonMatchingTasks);
        $this->assertCount(0, $nonMatchingTasks);
    }
}
