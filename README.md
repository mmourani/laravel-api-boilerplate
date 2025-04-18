# Laravel SaaS Boilerplate

A modular and secure SaaS boilerplate using **Laravel 12 (API-only)** with token-based authentication, ownership policies, and scalable structure.

[![Tests](https://github.com/mmourani/laravel-api-boilerplate/actions/workflows/tests.yml/badge.svg)](https://github.com/mmourani/laravel-api-boilerplate/actions/workflows/tests.yml)
[![Coverage Status](https://coveralls.io/repos/github/mmourani/laravel-api-boilerplate/badge.svg?branch=main)](https://coveralls.io/github/mmourani/laravel-api-boilerplate?branch=main)

## Features

### Laravel 12.x
- Built on the latest Laravel 12 framework
- Modern PHP 8.2+ syntax with type declarations
- Enhanced performance and security features

### Authentication

- Laravel Sanctum for API token auth
- Endpoints:
  - `POST /api/register`
  - `POST /api/login`
  - `POST /api/logout`
  - `GET  /api/user`
- Token returned on login, required for all protected endpoints

### Projects Module

- Model: `Project`
- Linked to user via `user_id`
- Full CRUD:
  - `GET /api/projects`
  - `POST /api/projects`
  - `GET /api/projects/{id}`
  - `PUT /api/projects/{id}`
  - `DELETE /api/projects/{id}`
- Ownership protected via `ProjectPolicy`

### Tasks Module

- Model: `Task`
- Linked to projects via `project_id`
- Fields: `title`, `done`, `priority`, `due_date`
- Endpoints (nested):
  - `GET /api/projects/{project}/tasks`
  - `POST /api/projects/{project}/tasks`
  - `GET /api/projects/{project}/tasks/{task}`
  - `PUT /api/projects/{project}/tasks/{task}`
  - `DELETE /api/projects/{project}/tasks/{task}`
- Filtering support:
  - `?priority=high`
  - `?done=true`
  - `?due_date=2025-04-25`
  - `?sort_by=due_date&direction=desc`
- Ownership protected via `ProjectPolicy`

## Requirements

- PHP 8.2+ (8.3+ recommended)
- Composer 2.5+
- MySQL 8.0+ or SQLite
- Node.js 18+ and NPM (if using frontend)

## Installation

```bash
# Clone the repository
git clone https://github.com/mmourani/laravel-api-boilerplate.git
cd laravel-api-boilerplate

# Install dependencies
composer install

# Copy environment file and generate application key
cp .env.example .env
php artisan key:generate

# Configure database in .env, then run migrations and seeders
php artisan migrate --seed

# Start development server
php artisan serve
```

## Testing

```bash
# Run all tests
composer test

# Run tests with coverage (requires Xdebug)
composer test:coverage

# Generate HTML coverage report
composer test:coverage-html

# Generate Clover XML for CI
composer test:coverage-clover
```
The HTML coverage report will be available in the `build/coverage` directory.

> **Note:** Code coverage reports require Xdebug to be installed and properly configured. If you're seeing warnings about Xdebug mode, make sure Xdebug is installed and the coverage mode is enabled. The test commands will attempt to enable it automatically, but you may need to configure it in your php.ini file.

### Testing with PHPUnit 11
### Testing with PHPUnit 11

This project uses PHPUnit 11 for testing, which requires PHP 8.2+. The test suite is configured to run with:

- In-memory SQLite database
- Preconfigured factories for all models
- Complete ownership policy testing
- API endpoint testing with JSON validation

## API Testing Examples

### Register a new user

```bash
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{"name": "Test", "email": "test@example.com", "password": "password", "password_confirmation": "password"}'
```

### Login

```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email": "test@example.com", "password": "password"}'
```

### Create a project

```bash
curl -X POST http://localhost:8000/api/projects \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"title":"Project Title","description":"Some description"}'
```

### Create a task

```bash
curl -X POST http://localhost:8000/api/projects/1/tasks \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"title":"Setup DB schema","priority":"high","due_date":"2025-04-25"}'
```

## Deployment

The application can be deployed to multiple environments (development, staging, production) using Laravel Forge.

```bash
# Configure Laravel Forge environments (one-time setup)
./bin/configure-forge.sh

# Deploy to an environment
./bin/deploy-forge.sh [environment] [branch]
```

For detailed deployment instructions, refer to the [deployment documentation](docs/deployment/README.md).

## Security and Policies

- ProjectPolicy applied via authorize() calls
- Only project owners can view/edit/delete their resources
- Sanctum token-based authentication
- CORS enabled for API access

## CI/CD Pipeline

This repository includes GitHub Actions workflows for:
- Automated testing on PHP 8.2+
- Code coverage reporting with Coveralls integration
- Continuous integration with dependency validation
- PHPUnit 11 test suite execution

### Laravel 12 Specific Features

- New routing and middleware system
- Improved validator with more precise type checking
- Enhanced Sanctum authentication (v4.x)
- Precognition support for form validation
- Improved error handling and debugging

## License

This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.
