# Changelog

## Version 2.0.0 (2025-04-18) - Laravel 12 Upgrade

### Major Framework Updates
- Upgraded from Laravel 10.x to Laravel 12.9.2
- Updated Sanctum from v3.x to v4.0.8
- Updated to PHPUnit 11.5.17 (from PHPUnit 10.x)
- Enhanced middleware implementation with Laravel 12 patterns
- Added support for Precognition
- Updated all route files to Laravel 12 conventions
- Fixed app bootstrapping process for Laravel 12

### Configuration Updates
- Added new cache configuration with support for array, database, file, memcached, redis, dynamodb and octane drivers
- Updated Sanctum configuration with token prefixing and expiration control
- Added CORS configuration in Sanctum for better API security
- Added token rate limiting to prevent API abuse
- Implemented Laravel 12 middleware priority system
- Set cache store configuration separate from cache driver
- Updated environment variables for Laravel 12 compatibility

### Security Enhancements
- Added Sanctum token prefixing for improved secret scanning (`laravel_sanctum_` prefix)
- Implemented 24-hour token expiration by default (configurable via env)
- Added rate limiting for token generation (6 requests per minute)
- Updated CORS configuration with more granular controls
- Improved middleware security stack with latest Laravel 12 protections
- Added precognition support for secure form validation
- Updated TrustProxies middleware with enhanced header detection

### Testing Suite Changes
- Updated to PHPUnit 11.5.17 with improved type checking
- Enhanced Xdebug integration for code coverage
- Added PHPStan for static analysis and deprecation detection
- Updated GitHub Actions workflow for Laravel 12 compatibility
- Added BC check for Laravel 12 deprecations
- Improved code coverage reporting with better debug output
- Added support for testing precognitive requests

### Middleware Changes
- Added HandlePrecognitiveRequests middleware
- Updated TrustProxies with enhanced header detection
- Updated CORS handling with Laravel 12 patterns
- Added middleware prioritization for optimized request handling
- Updated authentication middleware for new session handling
- Fixed RedirectIfAuthenticated to use proper Response types
- Updated CSRF protection with more secure defaults

### PHP Requirements
- Updated minimum PHP version to 8.2 (8.3+ recommended)
- Added required PHP extensions:
  - filter (required by Laravel 12)
  - fileinfo (required for file validation)
  - hash (required for security features)
  - session (required for session handling)
  - intl (recommended for internationalization)
- Added caching for .phpunit.cache directory in CI

### API Changes
- Updated API routing with cleaner structure
- Enhanced JSON response handling
- Improved error reporting with more context
- Added health check endpoint
- Updated authentication routes with stronger validation
- Projects and tasks API unchanged for backward compatibility

### Documentation Updates
- Updated README.md with Laravel 12 information
- Added CHANGELOG.md to track version changes
- Updated deployment instructions for Laravel 12
- Enhanced testing documentation for PHPUnit 11

