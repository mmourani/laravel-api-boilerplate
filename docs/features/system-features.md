# System Features

This document provides an overview of the core system features and architecture of the Laravel SaaS Boilerplate.

## Table of Contents
- [Laravel 12 Features](#laravel-12-features)
- [Authentication with Sanctum](#authentication-with-sanctum)
- [Ownership and Authorization Policies](#ownership-and-authorization-policies)
- [Database Configuration](#database-configuration)
- [Queue Processing System](#queue-processing-system)
- [Error Handling and Validation](#error-handling-and-validation)
- [Testing Infrastructure](#testing-infrastructure)

## Laravel 12 Features

The SaaS Boilerplate is built on Laravel 12, taking advantage of several key features:

### Enhanced Performance

Laravel 12 introduces several performance optimizations that are leveraged in this boilerplate:

- **Octane-Ready**: Pre-configured for high performance with Laravel Octane (optional)
- **Route Caching**: Optimized route registration for faster request handling
- **View Caching**: Precompiled Blade templates for improved rendering speed
- **Optimized Autoloading**: Fine-tuned Composer autoloading for faster class loading

### New Directory Structure

The boilerplate follows Laravel 12's updated directory structure:

- **App Namespace Organization**: Controllers, Models, and Resources are organized in a more intuitive way
- **Domain-Oriented Structure**: Core business logic separated from infrastructure concerns
- **Service Providers**: Modular service provider registration for cleaner bootstrapping
- **Configuration Files**: Enhanced configuration with environment-specific overrides

### PHP 8.2+ Features

The codebase takes advantage of modern PHP 8.2+ features:

- **Constructor Property Promotion**: Used throughout for concise class definitions
- **Typed Properties**: Strict type declarations for class properties
- **Union Types**: Applied where methods accept multiple parameter types
- **Match Expressions**: Used instead of complex switch-case blocks
- **Enums**: Implemented for type-safe constants like task priorities

## Authentication with Sanctum

The API uses Laravel Sanctum v4.x for authentication with the following key features:

### Token Authentication

```php
// Token creation (from AuthController)
$token = $user->createToken('api-token')->plainTextToken;
```

The system uses Sanctum's token-based authentication with the following configuration:

- **Token Lifetime**: 24 hours (configurable via `SANCTUM_TOKEN_EXPIRATION`)
- **Token Prefix**: Uses custom prefix for security scanning capabilities
- **Multiple Device Support**: Users can maintain active sessions on multiple devices
- **Token Abilities**: Structured to support future granular permissions

### Sanctum Configuration

The Sanctum configuration (`config/sanctum.php`) includes:

```php
'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', sprintf(
    '%s%s',
    'localhost,localhost:3000,127.0.0.1,127.0.0.1:8000,::1',
    Sanctum::currentApplicationUrlWithPort()
))),

'expiration' => env('SANCTUM_TOKEN_EXPIRATION', 60 * 24), // 24 hours by default

'token_prefix' => env('SANCTUM_TOKEN_PREFIX', 'laravel_sanctum_'),
```

### Rate Limiting

Sanctum is configured with rate limiting for token generation:

```php
'rate_limits' => [
    'token' => [
        'limit' => env('SANCTUM_RATE_LIMIT', 6),
        'interval' => env('SANCTUM_RATE_LIMIT_INTERVAL', 60),
    ],
],
```

This prevents abuse by limiting token creation attempts to 6 per minute by default.

### CORS Configuration

The API is configured to work with frontend applications through proper CORS settings:

```php
'cors' => [
    'allowed_origins' => explode(',', env('SANCTUM_ALLOWED_ORIGINS', '*')),
    'allowed_methods' => ['*'],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
],
```

## Ownership and Authorization Policies

The boilerplate implements robust authorization using Laravel Policies to control resource access.

### Policy Structure

Policies are defined for each major resource type (e.g., `ProjectPolicy`):

```php
class ProjectPolicy
{
    public function view(User $user, Project $project): bool
    {
        return $user->id === $project->user_id;
    }

    public function update(User $user, Project $project): bool
    {
        return $user->id === $project->user_id;
    }

    public function delete(User $user, Project $project): bool
    {
        return $user->id === $project->user_id;
    }
    
    public function restore(User $user, Project $project): bool
    {
        return $user->id === $project->user_id;
    }
}
```

### Policy Registration

Policies are automatically discovered and registered via Laravel's policy auto-discovery feature in Laravel 12.

### Usage in Controllers

The policies are enforced in controllers using the `authorize()` method:

```php
public function show(Project $project)
{
    $this->authorize('view', $project);
    
    return response()->json($project);
}
```

### Nested Resource Authorization

For nested resources like tasks within projects, authorization is handled at the parent level:

```php
public function index(Request $request, Project $project)
{
    $this->authorize('view', $project);
    
    // After authorization passes, access to project's tasks is allowed
    $query = $project->tasks();
    
    // ... additional logic
    
    return response()->json($query->get());
}
```

This ensures that users can only access tasks within projects they own.

### Policy Testing

Each policy is thoroughly tested to ensure proper authorization:

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

## Database Configuration

The boilerplate includes a flexible database configuration to support different environments.

### Environment-Specific Connections

The system supports multiple database connections configured for different environments:

- **Production**: MySQL or PostgreSQL for robust transactional support
- **Development**: MySQL/PostgreSQL for feature parity with production
- **Testing**: SQLite in-memory database for fast test execution

### Testing with SQLite

For testing, an in-memory SQLite database is used for maximum performance:

```php
// phpunit.xml configuration
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```

This allows tests to run isolated database operations without persistence between tests.

### Database Migrations

Migrations include all necessary schema definitions with:

- **Foreign Key Constraints**: Enforcing data integrity
- **Indexes**: Optimizing query performance
- **Soft Deletes**: Implementing non-destructive data removal
- **Timestamps**: Automatic creation/update tracking

### Model Factories

The system includes comprehensive model factories for generating test data:

- **User Factory**: Generates users with random credentials
- **Project Factory**: Creates projects with customizable attributes
- **Task Factory**: Generates tasks with different priorities and statuses

## Queue Processing System

The boilerplate implements a robust queue system for handling background tasks.

### Queue Configuration

The queue system is configured in `config/queue.php` with multiple drivers supported:

```php
'default' => env('QUEUE_CONNECTION', 'database'),

'connections' => [
    'sync' => [
        'driver' => 'sync',
    ],
    'database' => [
        'driver' => 'database',
        'connection' => env('DB_QUEUE_CONNECTION'),
        'table' => env('DB_QUEUE_TABLE', 'jobs'),
        'queue' => env('DB_QUEUE', 'default'),
        'retry_after' => (int) env('DB_QUEUE_RETRY_AFTER', 90),
        'after_commit' => false,
    ],
    'redis' => [
        'driver' => 'redis',
        'connection' => env('REDIS_QUEUE_CONNECTION', 'default'),
        'queue' => env('REDIS_QUEUE', 'default'),
        'retry_after' => (int) env('REDIS_QUEUE_RETRY_AFTER', 90),
        'block_for' => null,
        'after_commit' => false,
    ],
    // Other drivers...
],
```

### Queue Worker Configuration

For production, queue workers are configured using Supervisor:

```ini
[program:laravel-workers]
process_name=%(program_name)s_%(process_num)02d
command=php /home/forge/your-site.com/artisan queue:work redis --sleep=10 --tries=3
autostart=true
autorestart=true
numprocs=2
redirect_stderr=true
stdout_logfile=/home/forge/your-site.com/storage/logs/worker.log
stopwaitsecs=60
```

### Failed Job Handling

The system includes configuration for handling failed jobs:

```php
'failed' => [
    'driver' => env('QUEUE_FAILED_DRIVER', 'database-uuids'),
    'database' => env('DB_CONNECTION', 'sqlite'),
    'table' => 'failed_jobs',
],
```

Failed jobs are stored in a dedicated table with UUIDs for tracking and potential retry.

### Job Batching

For complex workflows, the system supports job batching:

```php
'batching' => [
    'database' => env('DB_CONNECTION', 'sqlite'),
    'table' => 'job_batches',
],
```

This allows for orchestrating multiple jobs as a single batch with completion callbacks.

## Error Handling and Validation

The boilerplate implements a comprehensive error handling and validation system.

### Exception Handling

Global exception handling is configured in `App\Exceptions\Handler`, which registers custom exception reporting callbacks.

### Validation System

The system uses Laravel's validation with enhanced features:

- **Type Checking**: Validates types (string, boolean, date) for incoming data
- **Rule Composition**: Combines multiple validation rules for complex requirements
- **Custom Error Messages**: User-friendly error messages for validation failures

Examples from controllers:

```php
// Project validation
$validated = $request->validate([
    'title' => 'required|string|max:255',
    'description' => 'nullable|string',
]);

// Task validation with enum-like constraints
$validated = $request->validate([
    'title' => 'required|string|max:255',
    'priority' => 'required|in:low,medium,high',
    'due_date' => 'nullable|date',
]);
```

### Comprehensive Error Responses

API responses include detailed error information:

```php
catch (\Illuminate\Validation\ValidationException $e) {
    return response()->json([
        'message' => 'Validation failed', 
        'errors' => $e->errors()
    ], 422);
} catch (\Illuminate\Auth\Access\AuthorizationException $e) {
    return response()->json([
        'message' => 'Unauthorized to create tasks in this project'
    ], 403);
} catch (\Exception $e) {
    return response()->json([
        'message' => 'Error creating task: ' . $e->getMessage()
    ], 500);
}
```

### Enhanced Debugging

The system includes comprehensive debugging tools for development:

```php
// Debug logging examples from ProjectController::restore()
\Log::debug("Request URL: " . $request->fullUrl());
\Log::debug("Request method: " . $request->method());
\Log::debug("Route parameters: " . json_encode($request->route()->parameters()));
\Log::debug("Project ID to restore: " . $id);
```

## Testing Infrastructure

The boilerplate implements a comprehensive testing infrastructure.

### Test Types

The system supports multiple test types:

- **Unit Tests**: For testing individual components
- **Feature Tests**: For testing complete HTTP workflows
- **Policy Tests**: For verifying authorization rules
- **Model Tests**: For checking database relationships

### Testing with SQLite

Tests run on an in-memory SQLite database for optimal performance:

```xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```

### Code Coverage

Code coverage is tracked using Xdebug and reported through:

- **Local HTML Reports**: For developer reference
- **Coveralls Integration**: For CI/CD pipeline visibility
- **Minimum Coverage Thresholds**: Enforcing quality standards

### CI/CD Integration

Tests are automatically run in the CI/CD pipeline using GitHub Actions with:

- **PHP 8.2+ Testing**: Ensuring compatibility with target environment
- **Multiple Environment Testing**: Verifying functionality across configurations
- **Coverage Reporting**: Maintaining visibility of test coverage
- **Pull Request Validation**: Preventing merges of untested code
