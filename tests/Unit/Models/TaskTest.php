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
}

