<?php

namespace Tests\Unit\Controllers;

use App\Http\Controllers\TaskController;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\UnitTestCase;

/**
 * Unit Tests for TaskController
 *
 * This test class provides comprehensive test coverage for the TaskController class,
 * testing all methods, conditional branches, and error handling scenarios.
 * It uses mocking to isolate the controller from the database and other dependencies,
 * ensuring true unit tests that focus solely on the controller's functionality.
 *
 * Testing Coverage:
 * - Filtering tasks by priority, status, due date
 * - Sorting tasks by different criteria
 * - Task creation with validation
 * - Task retrieval and updating
 * - Task deletion
 * - All error handling paths and status codes (403, 404, 422, 500)
 * - Authorization failures
 *
 * @group Controllers
 * @group Tasks
 */
class TaskControllerTest extends UnitTestCase
{
    /**
     * The controller instance being tested.
     *
     * @var TaskController
     */
    protected $controller;

    /**
     * Set up the test environment.
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->controller = new TaskController();
    }
    
    /**
     * Clean up the testing environment before the next test.
     *
     * @return void
     */
    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
    
    /*
    |--------------------------------------------------------------------------
    | INDEX METHOD TESTS
    |--------------------------------------------------------------------------
    |
    | These tests verify the functionality of retrieving tasks, including:
    | - Basic retrieval of tasks
    | - Filtering by priority, completion status, and due date
    | - Sorting by different fields
    | - Error handling for unauthorized access
    |
    */
    
    /**
     * Test the index method with basic inputs.
     * 
     * This test verifies that the index method correctly retrieves all tasks for a project
     * when no filtering or sorting parameters are specified. It ensures the tasks are
     * returned in the expected order with the correct HTTP status and response structure.
     */
    public function test_index_returns_tasks_for_project()
    {
        // Create mock task objects
        $task1 = Mockery::mock(Task::class);
        $task1->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $task1->shouldReceive('getAttribute')->with('title')->andReturn('Task 1');
        $task1->shouldReceive('jsonSerialize')->andReturn([
            'id' => 1,
            'title' => 'Task 1',
            'project_id' => 1
        ]);
        
        $task2 = Mockery::mock(Task::class);
        $task2->shouldReceive('getAttribute')->with('id')->andReturn(2);
        $task2->shouldReceive('getAttribute')->with('title')->andReturn('Task 2');
        $task2->shouldReceive('jsonSerialize')->andReturn([
            'id' => 2,
            'title' => 'Task 2',
            'project_id' => 1
        ]);
        
        $tasks = new Collection([$task1, $task2]);

        // Mock request
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('has')->with('priority')->andReturn(false);
        $request->shouldReceive('has')->with('done')->andReturn(false);
        $request->shouldReceive('has')->with('due_date')->andReturn(false);
        $request->shouldReceive('has')->with('sort_by')->andReturn(false);

        // Mock tasks query
        $tasksQuery = Mockery::mock(HasMany::class);
        $tasksQuery->shouldReceive('latest')->once()->andReturnSelf();
        $tasksQuery->shouldReceive('get')->once()->andReturn($tasks);

        // Mock project
        $project = Mockery::mock(Project::class);
        $project->shouldReceive('tasks')->once()->andReturn($tasksQuery);
        $project->shouldReceive('getAttribute')->with('id')->andReturn(1);

        // Mock authorization
        $this->instance('Illuminate\Contracts\Auth\Access\Gate', Mockery::mock('Illuminate\Contracts\Auth\Access\Gate', function ($mock) use ($project) {
            $mock->shouldReceive('authorize')->with('view', $project)->once();
        }));

        // Execute method
        $response = $this->controller->index($request, $project);

        // Debug the error response if we got a 500
        if ($response->getStatusCode() == 500) {
            $errorData = json_decode($response->getContent(), true);
            $this->fail('Got 500 error: ' . ($errorData['message'] ?? 'No message') . "\n" . print_r($errorData, true));
        }

        // Assert response
        $this->assertEquals(200, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertCount(2, $responseData);
        // Sort by ID to ensure consistent order
        usort($responseData, function($a, $b) {
            return $a['id'] <=> $b['id'];
        });
        $this->assertEquals('Task 1', $responseData[0]['title']);
        $this->assertEquals('Task 2', $responseData[1]['title']);
    }
    
    /**
     * Test the index method with filtering by priority.
     * 
     * This test verifies that the index method correctly filters tasks by priority
     * when a priority parameter is specified. It ensures only tasks with the matching
     * priority are returned with the correct HTTP status and response structure.
     */
    public function test_index_filters_by_priority()
    {
        // Mock dependencies
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('has')->with('priority')->andReturn(true);
        $request->shouldReceive('priority')->andReturn('high');
        $request->shouldReceive('has')->with('done')->andReturn(false);
        $request->shouldReceive('has')->with('due_date')->andReturn(false);
        $request->shouldReceive('has')->with('sort_by')->andReturn(false);
        $request->shouldReceive('all')->andReturn([
            'priority' => 'high'
        ]);

        $filteredTask = Mockery::mock(Task::class);
        $filteredTask->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $filteredTask->shouldReceive('getAttribute')->with('title')->andReturn('High Priority Task');
        $filteredTask->shouldReceive('getAttribute')->with('priority')->andReturn('high');
        $filteredTask->shouldReceive('jsonSerialize')->andReturn([
            'id' => 1,
            'title' => 'High Priority Task',
            'priority' => 'high'
        ]);
        
        $filteredTasks = new Collection([$filteredTask]);
        
        $tasksQuery = Mockery::mock(HasMany::class);
        $tasksQuery->shouldReceive('where')->with('priority', 'high')->once()->andReturnSelf();
        $tasksQuery->shouldReceive('latest')->once()->andReturnSelf();
        $tasksQuery->shouldReceive('get')->once()->andReturn($filteredTasks);
        
        $project = Mockery::mock(Project::class);
        $project->shouldReceive('tasks')->once()->andReturn($tasksQuery);
        $project->shouldReceive('getAttribute')->with('id')->andReturn(1);

        // Mock authorization
        $this->instance('Illuminate\Contracts\Auth\Access\Gate', Mockery::mock('Illuminate\Contracts\Auth\Access\Gate', function ($mock) use ($project) {
            $mock->shouldReceive('authorize')->with('view', $project)->once();
        }));

        // Execute method
        $response = $this->controller->index($request, $project);

        // Debug the error response if we got a 500
        if ($response->getStatusCode() == 500) {
            $errorData = json_decode($response->getContent(), true);
            $this->fail('Got 500 error: ' . ($errorData['message'] ?? 'No message') . "\n" . print_r($errorData, true));
        }
        
        // Assert response
        $this->assertEquals(200, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertCount(1, $responseData);
        $this->assertEquals('High Priority Task', $responseData[0]['title']);
        $this->assertEquals('high', $responseData[0]['priority']);
    }

    /**
     * Test the index method with filtering by completion status.
     * 
     * This test verifies that the index method correctly filters tasks by done status
     * when a done parameter is specified. It ensures only tasks with the matching
     * completion status are returned with the correct HTTP status and response structure.
     */
    public function test_index_filters_by_done_status()
    {
        // Mock dependencies
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('has')->with('priority')->andReturn(false);
        $request->shouldReceive('has')->with('done')->andReturn(true);
        $request->shouldReceive('done')->andReturn('true');
        $request->shouldReceive('has')->with('due_date')->andReturn(false);
        $request->shouldReceive('has')->with('sort_by')->andReturn(false);
        $request->shouldReceive('all')->andReturn([
            'done' => true
        ]);

        $filteredTask = Mockery::mock(Task::class);
        // Create a task with done status
        $filteredTask->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $filteredTask->shouldReceive('getAttribute')->with('title')->andReturn('Completed Task');
        $filteredTask->shouldReceive('getAttribute')->with('done')->andReturn(true);
        $filteredTask->shouldReceive('jsonSerialize')->andReturn([
            'id' => 1,
            'title' => 'Completed Task',
            'done' => true
        ]);
        
        $filteredTasks = new Collection([$filteredTask]);
        
        // Mock tasks query
        $tasksQuery = Mockery::mock(HasMany::class);
        $tasksQuery->shouldReceive('where')->with('done', true)->once()->andReturnSelf();
        $tasksQuery->shouldReceive('latest')->once()->andReturnSelf();
        $tasksQuery->shouldReceive('get')->once()->andReturn($filteredTasks);
        
        // Mock project
        $project = Mockery::mock(Project::class);
        $project->shouldReceive('tasks')->once()->andReturn($tasksQuery);
        $project->shouldReceive('getAttribute')->with('id')->andReturn(1);

        // Mock authorization
        $this->instance('Illuminate\Contracts\Auth\Access\Gate', Mockery::mock('Illuminate\Contracts\Auth\Access\Gate', function ($mock) use ($project) {
            $mock->shouldReceive('authorize')->with('view', $project)->once();
        }));

        // Execute method
        $response = $this->controller->index($request, $project);

        // Assert response
        $this->assertEquals(200, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertCount(1, $responseData);
        $this->assertEquals('Completed Task', $responseData[0]['title']);
        $this->assertEquals(true, $responseData[0]['done']);
    }

    /**
     * Test the index method with filtering by due date.
     * 
     * This test verifies that the index method correctly filters tasks by due date
     * when a due_date parameter is specified. It ensures only tasks with the matching
     * due date are returned with the correct HTTP status and response structure.
     */
    public function test_index_filters_by_due_date()
    {
        $dueDate = '2025-05-01';

        // Mock dependencies
        $request = Mockery::mock(Request::class);
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('has')->with('priority')->andReturn(false);
        $request->shouldReceive('has')->with('done')->andReturn(false);
        $request->shouldReceive('has')->with('due_date')->andReturn(true);
        $request->shouldReceive('due_date')->andReturn($dueDate);
        $request->shouldReceive('has')->with('sort_by')->andReturn(false);
        $request->shouldReceive('all')->andReturn([
            'due_date' => $dueDate
        ]);
        // Create a task with the specified due date
        $filteredTask = Mockery::mock(Task::class);
        $filteredTask->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $filteredTask->shouldReceive('getAttribute')->with('title')->andReturn('Task Due Tomorrow');
        $filteredTask->shouldReceive('getAttribute')->with('due_date')->andReturn($dueDate);
        $filteredTask->shouldReceive('jsonSerialize')->andReturn([
            'id' => 1,
            'title' => 'Task Due Tomorrow',
            'due_date' => $dueDate
        ]);
        
        $filteredTasks = new Collection([$filteredTask]);

        $tasksQuery = Mockery::mock(HasMany::class);
        $tasksQuery->shouldReceive('whereDate')->with('due_date', $dueDate)->once()->andReturnSelf();
        $tasksQuery->shouldReceive('latest')->once()->andReturnSelf();
        $tasksQuery->shouldReceive('get')->once()->andReturn($filteredTasks);

        $project = Mockery::mock(Project::class);
        $project->shouldReceive('tasks')->once()->andReturn($tasksQuery);
        $project->shouldReceive('getAttribute')->with('id')->andReturn(1);

        // Mock authorization
        $this->instance('Illuminate\Contracts\Auth\Access\Gate', Mockery::mock('Illuminate\Contracts\Auth\Access\Gate', function ($mock) use ($project) {
            $mock->shouldReceive('authorize')->with('view', $project)->once();
        }));

        // Execute method
        $response = $this->controller->index($request, $project);

        // Assert response
        $this->assertEquals(200, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertCount(1, $responseData);
        $this->assertEquals('Task Due Tomorrow', $responseData[0]['title']);
    }

    /**
     * Test the index method with sorting by a field.
     * 
     * This test verifies that the index method correctly sorts tasks by a specified field
     * and direction. It ensures tasks are returned in the correct order with the proper
     * HTTP status and response structure.
     */
    public function test_index_sorts_by_field()
    {
        // Mock dependencies
        $request = Mockery::mock(Request::class);
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('has')->with('priority')->andReturn(false);
        $request->shouldReceive('has')->with('done')->andReturn(false);
        $request->shouldReceive('has')->with('due_date')->andReturn(false);
        $request->shouldReceive('has')->with('sort_by')->andReturn(true);
        $request->shouldReceive('sort_by')->andReturn('title');
        $request->shouldReceive('input')->with('direction', 'asc')->andReturn('desc');
        $request->shouldReceive('all')->andReturn([
            'sort_by' => 'title',
            'direction' => 'desc'
        ]);
        $task1 = Mockery::mock(Task::class);
        $task1->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $task1->shouldReceive('getAttribute')->with('title')->andReturn('High Task');
        $task1->shouldReceive('getAttribute')->with('priority')->andReturn('high');
        
        $task2 = Mockery::mock(Task::class);
        $task2->shouldReceive('getAttribute')->with('id')->andReturn(2);
        $task2->shouldReceive('getAttribute')->with('title')->andReturn('Medium Task');
        $task2->shouldReceive('getAttribute')->with('priority')->andReturn('medium');
        
        $task3 = Mockery::mock(Task::class);
        $task3 = Mockery::mock(Task::class);
        $task3->shouldReceive('getAttribute')->with('id')->andReturn(3);
        $task3->shouldReceive('getAttribute')->with('title')->andReturn('Low Task');
        $task3->shouldReceive('getAttribute')->with('priority')->andReturn('low');
        // Create a sorted collection for testing
        $sortedTask1 = Mockery::mock(Task::class);
        $sortedTask1->shouldReceive('getAttribute')->with('id')->andReturn(2);
        $sortedTask1->shouldReceive('getAttribute')->with('title')->andReturn('Task Z');
        $sortedTask1->shouldReceive('jsonSerialize')->andReturn([
            'id' => 2,
            'title' => 'Task Z'
        ]);
        
        $sortedTask2 = Mockery::mock(Task::class);
        $sortedTask2->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $sortedTask2->shouldReceive('getAttribute')->with('title')->andReturn('Task A');
        $sortedTask2->shouldReceive('jsonSerialize')->andReturn([
            'id' => 1,
            'title' => 'Task A'
        ]);
        
        $sortedTasks = new Collection([$sortedTask1, $sortedTask2]);
        
        // Mock tasks query
        $tasksQuery = Mockery::mock(HasMany::class);
        $tasksQuery->shouldReceive('orderBy')->with('title', 'desc')->once()->andReturnSelf();
        $tasksQuery->shouldReceive('get')->once()->andReturn($sortedTasks);
        
        // Mock project
        $project = Mockery::mock(Project::class);
        $project->shouldReceive('tasks')->once()->andReturn($tasksQuery);
        $project->shouldReceive('getAttribute')->with('id')->andReturn(1);
        
        // Mock authorization
        $this->instance('Illuminate\Contracts\Auth\Access\Gate', Mockery::mock('Illuminate\Contracts\Auth\Access\Gate', function ($mock) use ($project) {
            $mock->shouldReceive('authorize')->with('view', $project)->once();
        }));
        
        // Execute method
        $response = $this->controller->index($request, $project);

        // Assert response
        $this->assertEquals(200, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertCount(2, $responseData);
        $this->assertCount(2, $responseData);
        $this->assertEquals('Task Z', $responseData[0]['title']);
        $this->assertEquals('Task A', $responseData[1]['title']);
    }

    /**
     * 
     * This test verifies that the index method correctly handles the special case of
     * sorting by priority, which requires custom SQL ordering logic. It ensures tasks
     * are returned in the correct priority order with the proper HTTP status.
     */
    public function test_index_sorts_by_priority()
    {
        // Mock dependencies
        $request = Mockery::mock(Request::class);
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('has')->with('priority')->andReturn(false);
        $request->shouldReceive('has')->with('done')->andReturn(false);
        $request->shouldReceive('has')->with('due_date')->andReturn(false);
        $request->shouldReceive('has')->with('sort_by')->andReturn(true);
        $request->shouldReceive('sort_by')->andReturn('priority');
        $request->shouldReceive('input')->with('direction', 'asc')->andReturn('desc');
        $request->shouldReceive('all')->andReturn([
            'sort_by' => 'priority',
            'direction' => 'desc'
        ]);
        $request->shouldReceive('input')->with('direction', 'asc')->andReturn('desc');
        $task1 = Mockery::mock(Task::class);
        $task1->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $task1->shouldReceive('getAttribute')->with('title')->andReturn('High Task');
        $task1->shouldReceive('getAttribute')->with('priority')->andReturn('high');
        $task1->shouldReceive('jsonSerialize')->andReturn([
            'id' => 1,
            'title' => 'High Task',
            'priority' => 'high'
        ]);
        
        $task2 = Mockery::mock(Task::class);
        $task2->shouldReceive('getAttribute')->with('id')->andReturn(2);
        $task2->shouldReceive('getAttribute')->with('title')->andReturn('Medium Task');
        $task2->shouldReceive('getAttribute')->with('priority')->andReturn('medium');
        $task2->shouldReceive('jsonSerialize')->andReturn([
            'id' => 2,
            'title' => 'Medium Task',
            'priority' => 'medium'
        ]);
        
        $task3 = Mockery::mock(Task::class);
        $task3->shouldReceive('getAttribute')->with('id')->andReturn(3);
        $task3->shouldReceive('getAttribute')->with('title')->andReturn('Low Task');
        $task3->shouldReceive('getAttribute')->with('priority')->andReturn('low');
        $task3->shouldReceive('jsonSerialize')->andReturn([
            'id' => 3,
            'title' => 'Low Task',
            'priority' => 'low'
        ]);
        
        $sortedTasks = new Collection([$task1, $task2, $task3]);
        $tasksQuery = Mockery::mock(HasMany::class);
        $tasksQuery = Mockery::mock(HasMany::class);
        $tasksQuery->shouldReceive('orderByRaw')->withAnyArgs()->once()->andReturnSelf();
        $tasksQuery->shouldReceive('get')->once()->andReturn($sortedTasks);

        $project = Mockery::mock(Project::class);
        $project->shouldReceive('tasks')->once()->andReturn($tasksQuery);
        $project->shouldReceive('getAttribute')->with('id')->andReturn(1);

        // Mock authorization
        $this->instance('Illuminate\Contracts\Auth\Access\Gate', Mockery::mock('Illuminate\Contracts\Auth\Access\Gate', function ($mock) use ($project) {
            $mock->shouldReceive('authorize')->with('view', $project)->once();
        }));

        // Execute method
        $response = $this->controller->index($request, $project);

        // Debug the error response if we got a 500
        if ($response->getStatusCode() == 500) {
            $errorData = json_decode($response->getContent(), true);
            $this->fail('Got 500 error: ' . ($errorData['message'] ?? 'No message') . "\n" . print_r($errorData, true));
        }

        // Assert response
        $this->assertEquals(200, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertCount(3, $responseData);
        $this->assertEquals('High Task', $responseData[0]['title']);
    }

    /**
     * Test index method when authorization fails.
     * 
     * This test verifies that the index method properly handles the case when a user
     * is not authorized to view tasks in a project. It ensures the method returns
     * a 403 Forbidden response with the appropriate error message.
     */
    public function test_index_handles_authorization_exception()
    {
        $request = Mockery::mock(Request::class);
        $project = Mockery::mock(Project::class);

        // Mock authorization exception
        $this->instance('Illuminate\Contracts\Auth\Access\Gate', Mockery::mock('Illuminate\Contracts\Auth\Access\Gate', function ($mock) use ($project) {
            $mock->shouldReceive('authorize')->with('view', $project)->once()
                ->andThrow(new AuthorizationException('Unauthorized to view tasks in this project'));
        }));

        // Execute method
        $response = $this->controller->index($request, $project);

        // Assert response
        $this->assertEquals(403, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('Unauthorized to view tasks in this project', $responseData['message']);
    }

    /*
    |--------------------------------------------------------------------------
    | STORE METHOD TESTS
    |--------------------------------------------------------------------------
    |
    | These tests verify the functionality of creating new tasks, including:
    | - Successful task creation with valid data
    | - Validation of required fields and data formats
    | - Error handling for validation failures
    | - Error handling for unauthorized access
    | - Error handling for generic exceptions
    |
    */

    /**
     * Test the store method for creating a task.
     * 
     * This test verifies that the store method correctly creates a new task when
     * provided with valid data. It checks that the task is properly created with
     * the expected attributes and that the response contains the created task
     * with a 201 Created status code.
     */
    public function test_store_creates_task()
    {
        // Mock request with validated data
        $request = Mockery::mock(Request::class);
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('validate')->once()->with([
            'title' => 'required|string|max:255',
            'priority' => 'required|in:low,medium,high',
            'due_date' => 'nullable|date',
        ])->andReturn([
            'title' => 'New Task',
            'priority' => 'medium',
            'due_date' => '2025-05-01',
        ]);
        $request->shouldReceive('all')->andReturn([
            'title' => 'New Task',
            'priority' => 'medium',
            'due_date' => '2025-05-01'
        ]);
        // Create new task
        $task = Mockery::mock(Task::class);
        $task->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $task->shouldReceive('getAttribute')->with('title')->andReturn('New Task');
        $task->shouldReceive('getAttribute')->with('priority')->andReturn('medium');
        $task->shouldReceive('getAttribute')->with('due_date')->andReturn('2025-05-01');
        $task->shouldReceive('getAttribute')->with('done')->andReturn(false);
        $task->shouldReceive('jsonSerialize')->andReturn([
            'id' => 1,
            'title' => 'New Task',
            'priority' => 'medium',
            'due_date' => '2025-05-01',
            'done' => false
        ]);
        $task->shouldReceive('toJson')->andReturn(json_encode([
            'id' => 1,
            'title' => 'New Task',
            'priority' => 'medium',
            'due_date' => '2025-05-01',
            'done' => false
        ]));
        // Mock tasks relationship
        $tasksRelation = Mockery::mock(HasMany::class);
        $tasksRelation->shouldReceive('create')->once()->with([
            'title' => 'New Task',
            'priority' => 'medium',
            'due_date' => '2025-05-01',
            'done' => false,
        ])->andReturn($task);

        $project = Mockery::mock(Project::class);
        $project->shouldReceive('tasks')->once()->andReturn($tasksRelation);
        $project->shouldReceive('getAttribute')->with('id')->andReturn(1);

        // Mock authorization
        $this->instance('Illuminate\Contracts\Auth\Access\Gate', Mockery::mock('Illuminate\Contracts\Auth\Access\Gate', function ($mock) use ($project) {
            $mock->shouldReceive('authorize')->with('update', $project)->once();
        }));

        // Execute method
        $response = $this->controller->store($request, $project);

        // Debug the error response if we got a 500
        if ($response->getStatusCode() == 500) {
            $errorData = json_decode($response->getContent(), true);
            $this->fail('Got 500 error: ' . ($errorData['message'] ?? 'No message') . "\n" . print_r($errorData, true));
        }

        // Assert response
        $this->assertEquals(201, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('New Task', $responseData['title']);
        $this->assertEquals('medium', $responseData['priority']);
        $this->assertEquals('2025-05-01', $responseData['due_date']);
        $this->assertFalse($responseData['done']);
    }

    /**
     * Test the store method with validation errors.
     * 
     * This test verifies that the store method properly handles validation errors
     * when invalid data is provided. It ensures that the method returns a 422
     * Unprocessable Entity response with validation error messages.
     */
    public function test_store_handles_validation_exception()
    {
        // Mock request with validation error
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('validate')->once()->with([
            'title' => 'required|string|max:255',
            'priority' => 'required|in:low,medium,high',
            'due_date' => 'nullable|date',
        ])->andThrow(ValidationException::withMessages([
            'title' => ['The title field is required.']
        ]));

        $project = Mockery::mock(Project::class);

        // Mock authorization
        $this->instance('Illuminate\Contracts\Auth\Access\Gate', Mockery::mock('Illuminate\Contracts\Auth\Access\Gate', function ($mock) use ($project) {
            $mock->shouldReceive('authorize')->with('update', $project)->once();
        }));

        // Execute method
        $response = $this->controller->store($request, $project);

        // Assert response
        $this->assertEquals(422, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('Validation failed', $responseData['message']);
        $this->assertArrayHasKey('errors', $responseData);
        $this->assertArrayHasKey('title', $responseData['errors']);
    }

    /**
     * Test store method when authorization fails.
     * 
     * This test verifies that the store method properly handles the case when a user
     * is not authorized to create tasks in a project. It ensures the method returns
     * a 403 Forbidden response with the appropriate error message.
     */
    public function test_store_handles_authorization_exception()
    {
        $request = Mockery::mock(Request::class);
        $project = Mockery::mock(Project::class);

        // Mock authorization exception
        $this->instance('Illuminate\Contracts\Auth\Access\Gate', Mockery::mock('Illuminate\Contracts\Auth\Access\Gate', function ($mock) use ($project) {
            $mock->shouldReceive('authorize')->with('update', $project)->once()
                ->andThrow(new AuthorizationException('Unauthorized to create tasks in this project'));
        }));

        // Execute method
        $response = $this->controller->store($request, $project);

        // Assert response
        $this->assertEquals(403, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('Unauthorized to create tasks in this project', $responseData['message']);
    }

    /**
     * Test store method with a generic exception.
     * 
     * This test verifies that the store method properly handles unexpected exceptions
     * that might occur during task creation. It ensures the method returns a 500
     * Internal Server Error response with an appropriate error message.
     */
    public function test_store_handles_generic_exception()
    {
        // Mock request with validated data
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('validate')->once()->with([
            'title' => 'required|string|max:255',
            'priority' => 'required|in:low,medium,high',
            'due_date' => 'nullable|date',
        ])->andReturn([
            'title' => 'New Task',
            'priority' => 'medium',
            'due_date' => '2025-05-01',
        ]);

        // Mock tasks relationship with exception
        $tasksRelation = Mockery::mock(HasMany::class);
        $tasksRelation->shouldReceive('create')->once()->andThrow(new \Exception('Database connection failed'));

        $project = Mockery::mock(Project::class);
        $project->shouldReceive('tasks')->once()->andReturn($tasksRelation);

        // Mock authorization
        $this->instance('Illuminate\Contracts\Auth\Access\Gate', Mockery::mock('Illuminate\Contracts\Auth\Access\Gate', function ($mock) use ($project) {
            $mock->shouldReceive('authorize')->with('update', $project)->once();
        }));

        // Execute method
        $response = $this->controller->store($request, $project);

        // Assert response
        $this->assertEquals(500, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('Error creating task: Database connection failed', $responseData['message']);
    }
    /*
    |--------------------------------------------------------------------------
    | SHOW METHOD TESTS
    |--------------------------------------------------------------------------
    |
    | These tests verify the functionality of retrieving a specific task, including:
    | - Successful task retrieval
    | - Error handling when task doesn't belong to the project
    | - Error handling for unauthorized access
    | - Error handling for generic exceptions
    |
    */

    /**
     * Test the show method for retrieving a task.
     * 
     * This test verifies that the show method correctly retrieves and returns a task
     * when the task belongs to the specified project. It checks that the response
     * contains the expected task data with a 200 OK status code.
     */
    public function test_show_returns_task()
    {
        $task = Mockery::mock(Task::class);
        $task->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $task->shouldReceive('getAttribute')->with('project_id')->andReturn(1);
        $task->shouldReceive('getAttribute')->with('title')->andReturn('Task 1');
        $task->shouldReceive('getAttribute')->with('priority')->andReturn('high');
        $task->shouldReceive('getAttribute')->with('done')->andReturn(false);
        $task->shouldReceive('jsonSerialize')->andReturn([
            'id' => 1,
            'title' => 'Task 1',
            'priority' => 'high',
            'done' => false
        ]);
        $task->shouldReceive('toJson')->andReturn(json_encode([
            'id' => 1,
            'title' => 'Task 1',
            'priority' => 'high',
            'done' => false
        ]));
        // No need to mock all() for show method as it doesn't use it

        $project = Mockery::mock(Project::class);
        $project->shouldReceive('getAttribute')->with('id')->andReturn(1);
        // Mock authorization
        $this->instance('Illuminate\Contracts\Auth\Access\Gate', Mockery::mock('Illuminate\Contracts\Auth\Access\Gate', function ($mock) use ($project) {
            $mock->shouldReceive('authorize')->with('view', $project)->once();
        }));

        // Execute method
        $response = $this->controller->show($project, $task);

        // Debug the error response if we got a 500
        if ($response->getStatusCode() == 500) {
            $errorData = json_decode($response->getContent(), true);
            $this->fail('Got 500 error: ' . ($errorData['message'] ?? 'No message') . "\n" . print_r($errorData, true));
        }

        // Assert response
        $this->assertEquals(200, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('Task 1', $responseData['title']);
        $this->assertEquals('high', $responseData['priority']);
        $this->assertFalse($responseData['done']);
    }
    /**
     * Test the show method with a task not in the project.
     * 
     * This test verifies that the show method properly handles the case when a task
     * doesn't belong to the specified project. It ensures the method returns a 404
     * Not Found response with an appropriate error message.
     */
    public function test_show_task_not_in_project()
    {
        $task = Mockery::mock(Task::class);
        $task->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $task->shouldReceive('getAttribute')->with('project_id')->andReturn(2); // different project ID
        $task->shouldReceive('getAttribute')->with('title')->andReturn('Task 1');

        $project = Mockery::mock(Project::class);
        $project->shouldReceive('getAttribute')->with('id')->andReturn(1);
        // Mock authorization
        $this->instance('Illuminate\Contracts\Auth\Access\Gate', Mockery::mock('Illuminate\Contracts\Auth\Access\Gate', function ($mock) use ($project) {
            $mock->shouldReceive('authorize')->with('view', $project)->once();
        }));

        // Execute method
        $response = $this->controller->show($project, $task);

        // Assert response
        $this->assertEquals(404, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('Task not found in this project', $responseData['message']);
    }

    /**
     * Test show method when authorization fails.
     * 
     * This test verifies that the show method properly handles the case when a user
     * is not authorized to view tasks in a project. It ensures the method returns
     * a 403 Forbidden response with the appropriate error message.
     */
    public function test_show_handles_authorization_exception()
    {
        $task = Mockery::mock(Task::class);
        $task->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $project = Mockery::mock(Project::class);

        // Mock authorization exception
        $this->instance('Illuminate\Contracts\Auth\Access\Gate', Mockery::mock('Illuminate\Contracts\Auth\Access\Gate', function ($mock) use ($project) {
            $mock->shouldReceive('authorize')->with('view', $project)->once()
                ->andThrow(new AuthorizationException('Unauthorized to view this task'));
        }));

        // Execute method
        $response = $this->controller->show($project, $task);

        // Assert response
        $this->assertEquals(403, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('Unauthorized to view this task', $responseData['message']);
    }

    /**
     * Test show method with a generic exception.
     * 
     * This test verifies that the show method properly handles unexpected exceptions
     * that might occur during task retrieval. It ensures the method returns a 500
     * Internal Server Error response with an appropriate error message.
     */
    public function test_show_handles_generic_exception()
    {
        $task = Mockery::mock(Task::class);
        $task->shouldReceive('getAttribute')->with('project_id')->andThrow(new \Exception('Database query failed'));

        $project = Mockery::mock(Project::class);

        // Mock authorization
        $this->instance('Illuminate\Contracts\Auth\Access\Gate', Mockery::mock('Illuminate\Contracts\Auth\Access\Gate', function ($mock) use ($project) {
            $mock->shouldReceive('authorize')->with('view', $project)->once();
        }));

        // Execute method
        $response = $this->controller->show($project, $task);

        // Assert response
        $this->assertEquals(500, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('Error retrieving task: Database query failed', $responseData['message']);
    }
    /*
    |--------------------------------------------------------------------------
    | UPDATE METHOD TESTS
    |--------------------------------------------------------------------------
    |
    | These tests verify the functionality of updating an existing task, including:
    | - Successful task update with valid data
    | - Error handling when task doesn't belong to the project
    | - Validation of required fields and data formats
    | - Error handling for validation failures
    | - Error handling for unauthorized access
    | - Error handling for generic exceptions
    |
    */

    /**
     * Test the update method for updating a task.
     * 
     * This test verifies that the update method correctly updates a task when
     * provided with valid data. It checks that the task is properly updated with
     * the expected attributes and that the response contains the updated task
     * with a 200 OK status code.
     */
    public function test_update_modifies_task()
    {
        // Mock request with validated data
        $request = Mockery::mock(Request::class);
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('validate')->once()->with([
            'title' => 'sometimes|required|string|max:255',
            'done' => 'boolean',
            'priority' => 'sometimes|required|in:low,medium,high',
            'due_date' => 'nullable|date',
        ])->andReturn([
            'title' => 'Updated Task',
            'priority' => 'high',
            'done' => true
        ]);
        $request->shouldReceive('all')->andReturn([
            'title' => 'Updated Task',
            'priority' => 'high',
            'done' => true
        ]);
        $task = Mockery::mock(Task::class);
        $task->shouldReceive('getAttribute')->with('project_id')->andReturn(1);
        $task->shouldReceive('update')->once()->with([
            'title' => 'Updated Task',
            'priority' => 'high',
            'done' => true
        ])->andReturnSelf();

        // Mock task data for the response
        // Mock task data for the response
        $task->shouldReceive('getAttribute')->with('priority')->andReturn('high');
        $task->shouldReceive('getAttribute')->with('done')->andReturn(true);
        $task->shouldReceive('jsonSerialize')->andReturn([
            'id' => 1,
            'title' => 'Updated Task',
            'priority' => 'high',
            'done' => true
        ]);
        $task->shouldReceive('toJson')->andReturn(json_encode([
            'id' => 1,
            'title' => 'Updated Task',
            'priority' => 'high',
            'done' => true
        ]));
        $project = Mockery::mock(Project::class);
        $project->shouldReceive('getAttribute')->with('id')->andReturn(1);
        // Mock authorization
        $this->instance('Illuminate\Contracts\Auth\Access\Gate', Mockery::mock('Illuminate\Contracts\Auth\Access\Gate', function ($mock) use ($project) {
            $mock->shouldReceive('authorize')->with('update', $project)->once();
        }));

        // Execute method
        $response = $this->controller->update($request, $project, $task);

        // Debug the error response if we got a 500
        if ($response->getStatusCode() == 500) {
            $errorData = json_decode($response->getContent(), true);
            $this->fail('Got 500 error: ' . ($errorData['message'] ?? 'No message') . "\n" . print_r($errorData, true));
        }

        // Assert response
        $this->assertEquals(200, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('Updated Task', $responseData['title']);
        $this->assertEquals('high', $responseData['priority']);
        $this->assertTrue($responseData['done']);
    }
    /**
     * Test the update method with a task not in the project.
     * 
     * This test verifies that the update method properly handles the case when a task
     * doesn't belong to the specified project. It ensures the method returns a 404
     * Not Found response with an appropriate error message.
     */
    public function test_update_task_not_in_project()
    {
        $request = Mockery::mock(Request::class);

        $task = Mockery::mock(Task::class);
        $task->shouldReceive('getAttribute')->with('project_id')->andReturn(2); // different project ID

        $project = Mockery::mock(Project::class);
        $project->shouldReceive('getAttribute')->with('id')->andReturn(1);
        // Mock authorization
        $this->instance('Illuminate\Contracts\Auth\Access\Gate', Mockery::mock('Illuminate\Contracts\Auth\Access\Gate', function ($mock) use ($project) {
            $mock->shouldReceive('authorize')->with('update', $project)->once();
        }));

        // Execute method
        $response = $this->controller->update($request, $project, $task);

        // Assert response
        $this->assertEquals(404, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('Task not found in this project', $responseData['message']);
    }

    /**
     * Test update method with validation errors.
     * 
     * This test verifies that the update method properly handles validation errors
     * when invalid data is provided. It ensures that the method returns a 422
     * Unprocessable Entity response with validation error messages.
     */
    public function test_update_handles_validation_exception()
    {
        // Mock request with validation error
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('validate')->once()->with([
            'title' => 'sometimes|required|string|max:255',
            'done' => 'boolean',
            'priority' => 'sometimes|required|in:low,medium,high',
            'due_date' => 'nullable|date',
        ])->andThrow(ValidationException::withMessages([
            'priority' => ['The priority must be one of: low, medium, high.']
        ]));

        $task = Mockery::mock(Task::class);
        $task->shouldReceive('getAttribute')->with('project_id')->andReturn(1);

        $project = Mockery::mock(Project::class);
        $project->shouldReceive('getAttribute')->with('id')->andReturn(1);
        // Mock authorization
        $this->instance('Illuminate\Contracts\Auth\Access\Gate', Mockery::mock('Illuminate\Contracts\Auth\Access\Gate', function ($mock) use ($project) {
            $mock->shouldReceive('authorize')->with('update', $project)->once();
        }));

        // Execute method
        $response = $this->controller->update($request, $project, $task);

        // Assert response
        $this->assertEquals(422, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('Validation failed', $responseData['message']);
        $this->assertArrayHasKey('errors', $responseData);
        $this->assertArrayHasKey('priority', $responseData['errors']);
    }

    /**
     * Test update method when authorization fails.
     * 
     * This test verifies that the update method properly handles the case when a user
     * is not authorized to update a task. It ensures the method returns a 403
     * Forbidden response with the appropriate error message.
     */
    public function test_update_handles_authorization_exception()
    {
        $request = Mockery::mock(Request::class);
        $task = Mockery::mock(Task::class);
        $project = Mockery::mock(Project::class);

        // Mock authorization exception
        $this->instance('Illuminate\Contracts\Auth\Access\Gate', Mockery::mock('Illuminate\Contracts\Auth\Access\Gate', function ($mock) use ($project) {
            $mock->shouldReceive('authorize')->with('update', $project)->once()
                ->andThrow(new AuthorizationException('Unauthorized to update this task'));
        }));

        // Execute method
        $response = $this->controller->update($request, $project, $task);

        // Assert response
        $this->assertEquals(403, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('Unauthorized to update this task', $responseData['message']);
    }

    /**
     * Test update method with a generic exception.
     * 
     * This test verifies that the update method properly handles unexpected exceptions
     * that might occur during task update. It ensures the method returns a 500
     * Internal Server Error response with an appropriate error message.
     */
    public function test_update_handles_generic_exception()
    {
        // Mock request with validated data
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('validate')->once()->with([
            'title' => 'sometimes|required|string|max:255',
            'done' => 'boolean',
            'priority' => 'sometimes|required|in:low,medium,high',
            'due_date' => 'nullable|date',
        ])->andReturn([
            'title' => 'Updated Task',
            'priority' => 'high',
            'done' => true
        ]);

        $task = Mockery::mock(Task::class);
        $task->shouldReceive('getAttribute')->with('project_id')->andReturn(1);
        $task->shouldReceive('update')->once()->andThrow(new \Exception('Database error occurred'));

        $project = Mockery::mock(Project::class);
        $project->shouldReceive('getAttribute')->with('id')->andReturn(1);

        // Mock authorization
        $this->instance('Illuminate\Contracts\Auth\Access\Gate', Mockery::mock('Illuminate\Contracts\Auth\Access\Gate', function ($mock) use ($project) {
            $mock->shouldReceive('authorize')->with('update', $project)->once();
        }));
        // Execute method
        $response = $this->controller->update($request, $project, $task);

        // Assert response
        $this->assertEquals(500, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertStringContainsString('Error updating task:', $responseData['message']);
    }
    /*
    |--------------------------------------------------------------------------
    | DESTROY METHOD TESTS
    |--------------------------------------------------------------------------
    |
    | These tests verify the functionality of deleting a task, including:
    | - Successful task deletion
    | - Error handling when task doesn't belong to the project
    | - Error handling for unauthorized access
    | - Error handling for generic exceptions
    |
    */

    /**
     * Test the destroy method for deleting a task.
     * 
     * This test verifies that the destroy method correctly deletes a task when 
     * the task belongs to the specified project. It checks that the task is properly
     * soft-deleted and that the response contains the expected message with a 200 OK
     * status code.
     */
    public function test_destroy_deletes_task()
    {
        $task = Mockery::mock(Task::class);
        $task->shouldReceive('getAttribute')->with('project_id')->andReturn(1);
        $task->shouldReceive('delete')->once()->andReturn(true);

        $project = Mockery::mock(Project::class);
        $project->shouldReceive('getAttribute')->with('id')->andReturn(1);
        // Mock authorization
        $this->instance('Illuminate\Contracts\Auth\Access\Gate', Mockery::mock('Illuminate\Contracts\Auth\Access\Gate', function ($mock) use ($project) {
            $mock->shouldReceive('authorize')->with('update', $project)->once();
        }));

        // Execute method
        $response = $this->controller->destroy($project, $task);

        // Assert response
        $this->assertEquals(200, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('Task soft-deleted successfully', $responseData['message']);
    }

    /**
     * Test the destroy method with a task not in the project.
     * 
     * This test verifies that the destroy method properly handles the case when a task
     * doesn't belong to the specified project. It ensures the method returns a 404
     * Not Found response with an appropriate error message.
     */
    public function test_destroy_task_not_in_project()
    {
        $task = Mockery::mock(Task::class);
        $task->shouldReceive('getAttribute')->with('project_id')->andReturn(2); // different project ID

        $project = Mockery::mock(Project::class);
        $project->shouldReceive('getAttribute')->with('id')->andReturn(1);
        // Mock authorization
        $this->instance('Illuminate\Contracts\Auth\Access\Gate', Mockery::mock('Illuminate\Contracts\Auth\Access\Gate', function ($mock) use ($project) {
            $mock->shouldReceive('authorize')->with('update', $project)->once();
        }));

        // Execute method
        $response = $this->controller->destroy($project, $task);

        // Assert response
        $this->assertEquals(404, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('Task not found in this project', $responseData['message']);
    }

    /**
     * Test destroy method when authorization fails.
     * 
     * This test verifies that the destroy method properly handles the case when a user
     * is not authorized to delete a task in a project. It ensures the method returns
     * a 403 Forbidden response with the appropriate error message.
     */
    public function test_destroy_handles_authorization_exception()
    {
        $task = Mockery::mock(Task::class);
        $project = Mockery::mock(Project::class);

        // Mock authorization exception
        $this->instance('Illuminate\Contracts\Auth\Access\Gate', Mockery::mock('Illuminate\Contracts\Auth\Access\Gate', function ($mock) use ($project) {
            $mock->shouldReceive('authorize')->with('update', $project)->once()
                ->andThrow(new AuthorizationException('Unauthorized to delete this task'));
        }));

        // Execute method
        $response = $this->controller->destroy($project, $task);

        // Assert response
        $this->assertEquals(403, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('Unauthorized to delete this task', $responseData['message']);
    }

    /**
     * Test destroy method with a generic exception.
     * 
     * This test verifies that the destroy method properly handles unexpected exceptions
     * that might occur during task deletion. It ensures the method returns a 500
     * Internal Server Error response with an appropriate error message.
     */
    public function test_destroy_handles_generic_exception()
    {
        $task = Mockery::mock(Task::class);
        $task->shouldReceive('getAttribute')->with('project_id')->andReturn(1);
        $task->shouldReceive('delete')->once()->andThrow(new \Exception('Database error during deletion'));

        $project = Mockery::mock(Project::class);
        $project->shouldReceive('getAttribute')->with('id')->andReturn(1);
        
        // Mock authorization
        $this->instance('Illuminate\Contracts\Auth\Access\Gate', Mockery::mock('Illuminate\Contracts\Auth\Access\Gate', function ($mock) use ($project) {
            $mock->shouldReceive('authorize')->with('update', $project)->once();
        }));

        // Execute method
        $response = $this->controller->destroy($project, $task);

        // Assert response
        $this->assertEquals(500, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertStringContainsString('Error deleting task:', $responseData['message']);
    }
}
