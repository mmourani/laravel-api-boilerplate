# Testing

This document provides comprehensive information about the testing approach used in the SaaS Boilerplate project.

## Overview

The application uses PHPUnit for testing and follows Laravel's testing best practices. The test suite consists of:

1. **Unit Tests** - For testing individual components in isolation
2. **Feature Tests** - For testing complete features through HTTP requests

We aim to maintain a test coverage of at least 90% for all code.

## Test Environment

The testing environment uses:

- SQLite in-memory database for fast test execution
- Mock services for external dependencies
- Factories for generating test data

## Running Tests

You can run the tests using the following commands:

```bash
# Run all tests
php artisan test

# Run with coverage report (requires Xdebug)
XDEBUG_MODE=coverage php artisan test --coverage

# Run only unit tests
php artisan test --testsuite=Unit

# Run only feature tests
php artisan test --testsuite=Feature
```

## Unit Tests

### Model Tests

Unit tests for models ensure that relationships, attributes, and model behavior work as expected.

#### User Model (`tests/Unit/Models/UserTest.php`)

Tests for the User model include:
- Relationship with Projects (hasMany)
- Attribute casting (email_verified_at, password)
- Hidden attributes (password, remember_token)
- Fillable attributes

#### Project Model (`tests/Unit/Models/ProjectTest.php`)

Tests for the Project model include:
- Relationship with User (belongsTo)
- Relationship with Tasks (hasMany)
- Fillable attributes
- Factory creation
- Cascading deletes for tasks

#### Task Model (`tests/Unit/Models/TaskTest.php`)

Tests for the Task model include:
- Relationship with Project (belongsTo)
- Attribute casting (done as boolean, due_date as datetime)
- Fillable attributes
- Priority validation
- Factory states (done, pending, priority)

### Policy Tests

Unit tests for policies ensure that authorization logic works correctly.

#### Project Policy (`tests/Unit/Policies/ProjectPolicyTest.php`)

Tests for the ProjectPolicy include:
- View authorization (owner vs. non-owner)
- Update authorization (owner vs. non-owner)
- Delete authorization (owner vs. non-owner)
- Consistency across policy methods

## Feature Tests

Feature tests ensure that the API endpoints and controllers function correctly from an end-to-end perspective.

### Authentication Tests (`tests/Feature/Auth/AuthenticationTest.php`)

Tests for authentication features include:
- User registration
- User login
- User profile retrieval
- User logout

### Project Tests (`tests/Feature/ProjectTest.php`)

Tests for project management features include:
- Creating projects
- Viewing projects
- Updating projects
- Deleting projects
- Ownership restrictions

### Task Tests (`tests/Feature/TaskTest.php`)

Tests for task management features include:
- Creating tasks
- Viewing tasks
- Updating tasks
- Deleting tasks
- Filtering tasks by priority, completion status, and due date
- Sorting tasks by various criteria

## Factories

The project includes factories to generate test data:

1. `UserFactory` - For creating users
2. `ProjectFactory` - For creating projects, linked to users
3. `TaskFactory` - For creating tasks, linked to projects, with various states for task status and priority

## CI/CD Integration

Our GitHub Actions workflow runs all tests automatically on each push and pull request. The workflow:

1. Sets up PHP 8.2
2. Configures the test environment
3. Runs all tests
4. Generates and uploads code coverage reports to Coveralls

For details, see the workflow configuration in `.github/workflows/tests.yml`.

## Writing New Tests

When adding new features or fixing bugs, follow these guidelines:

1. Write tests before implementing the feature (TDD approach)
2. Ensure both unit and feature tests are included
3. Use factories and database seeders for test data
4. Keep tests isolated and avoid dependencies between tests
5. Use meaningful test method names that describe what's being tested

## Best Practices

- Keep tests focused and specific
- Clean up after your tests
- Don't test the framework itself
- Mock external services
- Maintain test database seeding for consistent test environments
- Use database transactions where possible to improve test speed

