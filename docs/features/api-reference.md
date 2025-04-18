# API Reference

This document provides comprehensive documentation for the SaaS Boilerplate API, including authentication, error handling, endpoints, and examples.

## Table of Contents

- [Introduction](#introduction)
- [API Base URL](#api-base-url)
- [Authentication](#authentication)
- [Error Handling](#error-handling)
- [API Endpoints](#api-endpoints)
  - [Authentication Endpoints](#authentication-endpoints)
  - [Project Endpoints](#project-endpoints)
  - [Task Endpoints](#task-endpoints)

## Introduction

The SaaS Boilerplate API provides a RESTful interface for managing user authentication, projects, and tasks. The API uses JSON for request and response bodies.

## API Base URL

```
https://your-app-domain.com/api
```

For local development:

```
http://localhost:8000/api
```

## Authentication

The API uses Laravel Sanctum for authentication. Sanctum provides a lightweight authentication system for SPAs (Single Page Applications) and mobile applications.

### Token-based Authentication

After authentication, you'll receive an API token that must be included in the `Authorization` header of all subsequent requests:

```
Authorization: Bearer YOUR_API_TOKEN
```

## Error Handling

The API uses standard HTTP status codes to indicate the success or failure of requests.

### Status Codes

| Code | Description |
|------|-------------|
| 200  | OK - The request was successful |
| 201  | Created - A new resource was successfully created |
| 400  | Bad Request - The request could not be understood or had invalid parameters |
| 401  | Unauthorized - Authentication failed or user doesn't have permissions |
| 403  | Forbidden - User is authenticated but does not have permission |
| 404  | Not Found - The requested resource was not found |
| 422  | Unprocessable Entity - Validation errors |
| 500  | Server Error - Something went wrong on the server |

### Error Response Format

All error responses follow a consistent format:

```json
{
    "message": "A human-readable error message",
    "errors": {
        "field_name": [
            "Validation error for this field"
        ]
    }
}
```

For validation errors (422 status code), the `errors` field contains field-specific validation messages:

```json
{
    "message": "Validation failed",
    "errors": {
        "title": [
            "The title field is required"
        ],
        "email": [
            "The email field must be a valid email address"
        ]
    }
}
```

### Authorization Errors

When a user attempts to access a resource they don't have permission for, a 403 Forbidden response is returned:

```json
{
    "message": "Unauthorized to view this project"
}
```

### Server Errors

When an unexpected server error occurs, a 500 response is returned with error details:

```json
{
    "message": "Error retrieving tasks: [error details]"
}
```

In the development environment, more detailed debugging information is available in the logs.

## API Endpoints

### Authentication Endpoints

#### Register a new user

```
POST /api/auth/register
```

Request:

```json
{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "secure_password",
    "password_confirmation": "secure_password"
}
```

Response (201 Created):

```json
{
    "user": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com",
        "created_at": "2025-04-18T13:30:45.000000Z"
    },
    "token": "1|a1b2c3d4e5f6g7h8i9j0..."
}
```

#### Login

```
POST /api/auth/login
```

Request:

```json
{
    "email": "john@example.com",
    "password": "secure_password"
}
```

Response (200 OK):

```json
{
    "user": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com"
    },
    "token": "2|k1l2m3n4o5p6q7r8s9t0..."
}
```

#### Get authenticated user

```
GET /api/auth/user
```

Response (200 OK):

```json
{
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "created_at": "2025-04-18T13:30:45.000000Z",
    "updated_at": "2025-04-18T13:30:45.000000Z",
    "email_verified_at": null
}
```

#### Logout

```
POST /api/auth/logout
```

Response (200 OK):

```json
{
    "message": "Logged out successfully"
}
```

### Project Endpoints

#### List all projects

```
GET /api/projects
```

Response (200 OK):

```json
[
    {
        "id": 1,
        "user_id": 1,
        "title": "Mobile App Development",
        "description": "Develop a mobile app for customer engagement",
        "created_at": "2025-04-18T13:35:45.000000Z",
        "updated_at": "2025-04-18T13:35:45.000000Z",
        "deleted_at": null
    },
    {
        "id": 2,
        "user_id": 1,
        "title": "Website Redesign",
        "description": "Update the marketing website with new branding",
        "created_at": "2025-04-18T13:40:45.000000Z",
        "updated_at": "2025-04-18T13:40:45.000000Z",
        "deleted_at": null
    }
]
```

#### Create a project

```
POST /api/projects
```

Request:

```json
{
    "title": "New Marketing Campaign",
    "description": "Q3 product launch marketing campaign"
}
```

Response (201 Created):

```json
{
    "id": 3,
    "user_id": 1,
    "title": "New Marketing Campaign",
    "description": "Q3 product launch marketing campaign",
    "created_at": "2025-04-18T14:20:45.000000Z",
    "updated_at": "2025-04-18T14:20:45.000000Z",
    "deleted_at": null
}
```

#### Get a specific project

```
GET /api/projects/{project_id}
```

Response (200 OK):

```json
{
    "id": 3,
    "user_id": 1,
    "title": "New Marketing Campaign",
    "description": "Q3 product launch marketing campaign",
    "created_at": "2025-04-18T14:20:45.000000Z",
    "updated_at": "2025-04-18T14:20:45.000000Z",
    "deleted_at": null
}
```

#### Update a project

```
PUT /api/projects/{project_id}
```

Request:

```json
{
    "title": "Updated Marketing Campaign",
    "description": "Q3 and Q4 product launch marketing campaign"
}
```

Response (200 OK):

```json
{
    "id": 3,
    "user_id": 1,
    "title": "Updated Marketing Campaign",
    "description": "Q3 and Q4 product launch marketing campaign",
    "created_at": "2025-04-18T14:20:45.000000Z",
    "updated_at": "2025-04-18T14:25:45.000000Z",
    "deleted_at": null
}
```

#### Delete a project

```
DELETE /api/projects/{project_id}
```

Response (200 OK):

```json
{
    "message": "Project deleted successfully"
}
```

#### Restore a soft-deleted project

```
POST /api/projects/{project_id}/restore
```

Response (200 OK):

```json
{
    "message": "Project restored successfully",
    "project": {
        "id": 3,
        "user_id": 1,
        "title": "Updated Marketing Campaign",
        "description": "Q3 and Q4 product launch marketing campaign",
        "created_at": "2025-04-18T14:20:45.000000Z",
        "updated_at": "2025-04-18T14:25:45.000000Z",
        "deleted_at": null
    }
}
```

### Task Endpoints

Tasks are always associated with a project and use nested routing.

#### List all tasks for a project

```
GET /api/projects/{project_id}/tasks
```

Response (200 OK):

```json
[
    {
        "id": 1,
        "project_id": 3,
        "title": "Create social media assets",
        "priority": "high",
        "due_date": "2025-05-01",
        "done": false,
        "created_at": "2025-04-18T14:30:45.000000Z",
        "updated_at": "2025-04-18T14:30:45.000000Z"
    },
    {
        "id": 2,
        "project_id": 3,
        "title": "Draft press release",
        "priority": "medium",
        "due_date": "2025-04-25",
        "done": false,
        "created_at": "2025-04-18T14:32:45.000000Z",
        "updated_at": "2025-04-18T14:32:45.000000Z"
    }
]
```

#### Filtering Tasks

The tasks endpoint supports various filtering options:

```
GET /api/projects/{project_id}/tasks?priority=high
GET /api/projects/{project_id}/tasks?done=true
GET /api/projects/{project_id}/tasks?due_date=2025-05-01
```

#### Sorting Tasks

Tasks can be sorted by different fields:

```
GET /api/projects/{project_id}/tasks?sort_by=priority&direction=desc
GET /api/projects/{project_id}/tasks?sort_by=due_date&direction=asc
```

#### Create a task

```
POST /api/projects/{project_id}/tasks
```

Request:

```json
{
    "title": "Schedule media interviews",
    "priority": "medium",
    "due_date": "2025-05-10"
}
```

Response (201 Created):

```json
{
    "id": 3,
    "project_id": 3,
    "title": "Schedule media interviews",
    "priority": "medium",
    "due_date": "2025-05-10",
    "done": false,
    "created_at": "2025-04-18T14:40:45.000000Z",
    "updated_at": "2025-04-18T14:40:45.000000Z"
}
```

#### Get a specific task

```
GET /api/projects/{project_id}/tasks/{task_id}
```

Response (200 OK):

```json
{
    "id": 3,
    "project_id": 3,
    "title": "Schedule media interviews",
    "priority": "medium",
    "due_date": "2025-05-10",
    "done": false,
    "created_at": "2025-04-18T14:40:45.000000Z",
    "updated_at": "2025-04-18T14:40:45.000000Z"
}
```

#### Update a task

```
PUT /api/projects/{project_id}/tasks/{task_id}
```

Request:

```json
{
    "title": "Schedule media interviews",
    "priority": "high",
    "due_date": "2025-05-05",
    "done": true
}
```

Response (200 OK):

```json
{
    "id": 3,
    "project_id": 3,
    "title": "Schedule media interviews",
    "priority": "high",
    "due_date": "2025-05-05",
    "done": true,
    "created_at": "2025-04-18T14:40:45.000000Z",
    "updated_at": "2025-04-18T14:45:45.000000Z"
}
```

#### Delete a task

```
DELETE /api/projects/{project_id}/tasks/{task_id}
```

Response (200 OK):

```json
{
    "message": "Task soft-deleted successfully"
}
```

## Error Handling Best Practices

The API implements several error handling best practices to make debugging and usage easier:

1. **Consistent Error Format**: All errors follow the same JSON structure
2. **Appropriate HTTP Status Codes**: Status codes match the error type
3. **Validation Error Details**: Field-specific validation messages
4. **Descriptive Error Messages**: User-friendly messages explain what went wrong
5. **Enhanced Debugging**: In development mode, additional context is logged
6. **Try-Catch Blocks**: All controller methods use try-catch for error handling
7. **Policy-Based Authorization**: Uses Laravel policies for consistent authorization checks

### Example: Debug Information for Errors

For actions like project restoration, detailed debug information is logged:

```php
// Request information
\Log::debug("Request URL: " . $request->fullUrl());
\Log::debug("Request method: " . $request->method());
\Log::debug("Route parameters: " . json_encode($request->route()->parameters()));
\Log::debug("Project ID to restore: " . $id);

// Project state
\Log::debug("Project instance received: Yes");
\Log::debug("Project ID: " . $project->id);
\Log::debug("Project is trashed? " . ($project->trashed() ? 'Yes' : 'No'));
```

This provides comprehensive information for troubleshooting issues.

## Authorization and Ownership

All resources (projects and tasks) are protected by ownership policies:

1. Users can only access their own projects
2. Tasks inherit their parent project's authorization
3. All policy checks are enforced in controllers using `$this->authorize()`
4. Failed authorization results in a 403 Forbidden response

## Type Checking and Validation

The API implements strict validation with type checking:

1. **String Validation**: `string|max:255`
2. **Boolean Conversion**: `filter_var($request->done, FILTER_VALIDATE_BOOLEAN)`
3. **Date Validation**: `nullable|date`
4. **Enum-like Validation**: `required|in:low,medium,high`
