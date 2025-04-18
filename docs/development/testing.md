# Testing Documentation

This document provides comprehensive information about the testing methodology, organization, and best practices for the Laravel SaaS Boilerplate project.

## Table of Contents
- [Testing Approach](#testing-approach)
- [Test Organization](#test-organization)
- [Running Tests](#running-tests)
- [Coverage Requirements](#coverage-requirements)
- [CI/CD Integration](#cicd-integration)
- [Testing Best Practices](#testing-best-practices)
- [Real-World Examples](#real-world-examples)
- [Troubleshooting](#troubleshooting)

## Testing Approach

The Laravel SaaS Boilerplate follows a comprehensive testing approach that combines:

- **Unit Tests**: Testing individual components in isolation
- **Feature Tests**: Testing complete features through HTTP requests
- **Policy Tests**: Ensuring proper authorization rules
- **Model Tests**: Verifying relationships and attribute behavior

Our testing philosophy emphasizes:
- **Test-Driven Development (TDD)** where appropriate
- **High code coverage** (minimum 90%)
- **Real-world scenarios** rather than implementation details
- **Isolated tests** that don't depend on each other

## Test Organization

The test directory is organized into two main sections:

```
tests/
├── Feature/             # End-to-end and integration tests
│   ├── Auth/            # Authentication tests
│   ├── ProjectTest.php  # Project feature tests
│   └── TaskTest.php     # Task feature tests
├── Unit/                # Unit tests for isolated components
│   ├── Controllers/     # Tests for controller methods
│   ├── Models/          # Tests for model behavior
│   └── Policies/        # Tests for authorization policies
└── TestCase.php         # Base test class
```

### Feature Tests

Feature tests simulate HTTP requests to your application and test the responses. They cover:

- API endpoints and controllers
- Request validation
- Response structures
- Authorization checks
- Database interactions

### Unit Tests

Unit tests focus on individual components and verify:

- Model relationships
- Policy decision logic
- Attribute casting and mutations
- Service class behavior
- Controller methods and edge cases
- Validation rules

## Running Tests

Use the following commands to run tests:

```bash
# Run all tests
composer test

# Run tests with coverage report
composer test:coverage

# Run specific test file
php artisan test tests/Feature/ProjectTest.php

# Run specific test method
php artisan test --filter test_user_can_create_project

# Generate HTML coverage report
composer test:coverage-html
```

## Coverage Requirements

We maintain strict coverage requirements to ensure code quality:

- **Minimum Line Coverage**: 90% (current: ~97%)
- **All Controllers**: 100% coverage (TaskController: 97.33%)
- **All Models**: 100% coverage 
- **All Policies**: 100% coverage

Coverage is tracked in the CI/CD pipeline using Coveralls, which provides:

- Historical coverage trends
- File-by-file coverage data
- Pull request coverage changes

Local coverage reports can be generated with:

```bash
composer test:coverage-html
```

This creates a report in `build/coverage/` that you can view in your browser.

## CI/CD Integration

Our GitHub Actions workflow (`.github/workflows/tests.yml`) automatically runs tests on:

- Every push to the main branch
- Every push to feature branches
- Every pull request

The workflow:

1. Sets up PHP 8.4 with extensions and Xdebug
2. Configures a SQLite in-memory database
3. Installs dependencies (with caching)
4. Validates Laravel 12 compatibility
5. Runs all tests with coverage
6. Reports code coverage to Coveralls
7. Verifies minimum coverage thresholds (90%)
8. Archives coverage reports as artifacts

The Coveralls integration provides:
- Detailed coverage reports for each commit
- Historical coverage trends
- Pull request coverage changes
- Badge integration in README.md

Pull requests cannot be merged unless:
- All tests pass
- Coverage requirements are met (minimum 90%)
- No critical deprecation warnings for Laravel 12

## Testing Best Practices

### General Guidelines

1. **Test Behavior, Not Implementation**
   - Focus on what the code does, not how it does it
   - This allows refactoring without breaking tests

2. **One Assertion Per Test**
   - Each test should verify one specific behavior
   - Makes tests more focused and easier to maintain

3. **Arrange, Act, Assert Pattern**
   - Arrange: Set up test data and conditions
   - Act: Perform the action being tested
   - Assert: Verify the expected outcome

4. **Use Meaningful Test Names**
   - Describe what the test is checking
   - Example: `test_user_can_create_project`

5. **Isolate Tests**
   - Tests should not depend on each other
   - Each test should set up its own data

### Laravel-Specific Practices

1. **Use Factories for Test Data**
   - Create models using factories
   - Customize only what's relevant to the test

2. **Use DatabaseTransactions**
   - Tests run in transactions for better performance
   - No need to clean up after tests

3. **Test Against Contracts**
   - Test against interfaces when possible
   - Makes tests resilient to implementation changes

4. **Test Edge Cases**
   - Test validation failures
   - Test authorization boundaries
   - Test error handling

## Real-World Examples

### Feature Test Example

From `tests/Feature/ProjectTest.php`:

```php
public function test_user_can_create_project(): void
{
    // Arrange
    $user = User::factory()->create();
    
    // Act
    $response = $this->actingAs($user)
        ->postJson("/api/projects", [
            'title' => 'Test Project', 
            'description' => 'Test Description',
        ]);
    
    // Assert
    $response->assertStatus(201)
        ->assertJsonStructure([
            'id', 'title', 'description'
        ]);
    
    $this->assertDatabaseHas('projects', [
        'title' => 'Test Project',
        'user_id' => $user->id,
    ]);
}
```

This test:
1. Creates a user using a factory
2. Makes a POST request to create a project
3. Verifies the response code and structure
4. Confirms the project was stored in the database

### Unit Test Example

From `tests/Unit/Models/UserTest.php`:

```php
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
```

This test:
1. Verifies the relationship method exists
2. Checks the relationship type
3. Tests with actual database records
4. Verifies the relationship works as expected

### Policy Test Example

From `tests/Unit/Policies/ProjectPolicyTest.php`:

```php
public function test_owner_can_view_project(): void
{
    $this->assertTrue(
        $this->policy->view($this->owner, $this->project)
    );
}

public function test_non_owner_cannot_view_project(): void
{
    $this->assertFalse(
        $this->policy->view($this->nonOwner, $this->project)
    );
}
```

These tests:
1. Verify that project owners can view their projects
2. Verify that non-owners cannot view others' projects

### Controller Unit Test Example

From `tests/Unit/Controllers/TaskControllerTest.php`:

```php
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

    // Assert response
    $this->assertEquals(200, $response->getStatusCode());
    $responseData = json_decode($response->getContent(), true);
    $this->assertCount(1, $responseData);
    $this->assertEquals('High Priority Task', $responseData[0]['title']);
    $this->assertEquals('high', $responseData[0]['priority']);
}
```

This test:
1. Mocks all dependencies (Request, Task, Project, etc.)
2. Sets up expectations for each mock method
3. Calls the controller method directly
4. Verifies the response status and content
5. Tests a specific filtering scenario (by priority)

## Troubleshooting

### Common Issues

1. **Tests failing after database schema changes**
   - Run `php artisan migrate:fresh --env=testing`
   - Review migrations to ensure backward compatibility

2. **Coverage not generating**
   - Ensure Xdebug is installed and configured
   - Check for `XDEBUG_MODE=coverage` in environment

3. **Slow tests**
   - Use SQLite in-memory database for testing
   - Use database transactions for faster cleanup
   - Consider parallel testing for larger test suites

4. **Flaky tests**
   - Identify tests that fail intermittently
   - Check for external dependencies
   - Ensure tests don't depend on execution order

### Getting Help

If you encounter issues with the testing framework:

1. Check the Laravel testing documentation
2. Review the PHPUnit documentation
3. Ask in the project's discussion forum

---

## Laravel 12 Specific Testing Notes

### Environment Setup

Laravel 12 testing uses an improved environment configuration in `phpunit.xml`:

```xml
<php>
    <env name="APP_ENV" value="testing"/>
    <env name="DB_CONNECTION" value="sqlite"/>
    <env name="DB_DATABASE" value=":memory:"/>
    <env name="CACHE_DRIVER" value="array"/>
    <env name="MAIL_MAILER" value="array"/>
    <env name="QUEUE_CONNECTION" value="sync"/>
    <!-- New in Laravel 12 -->
    <env name="PRECOGNITION_ENABLED" value="true"/>
</php>
```

### Coverage Configuration

Coverage is configured in `phpunit.xml` with the following settings:

```xml
<coverage>
    <report>
        <clover outputFile="build/logs/clover.xml"/>
        <html outputDirectory="build/coverage" lowUpperBound="50" highLowerBound="90"/>
        <text outputFile="build/coverage.txt" showUncoveredFiles="false" showOnlySummary="true"/>
    </report>
</coverage>
```

### Xdebug Configuration

For optimal test performance and coverage reporting, configure Xdebug with:

```ini
[xdebug]
xdebug.mode=develop,debug,coverage
xdebug.client_host=localhost
xdebug.client_port=9003
```

When running tests with coverage:

```bash
XDEBUG_MODE=coverage php artisan test --coverage
```

*Last updated: April 19, 2025*

