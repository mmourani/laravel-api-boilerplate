
# Deployment Automation

This document describes the automated deployment system for the SaaS Boilerplate application.

## Overview

The deployment automation system consists of several scripts that handle different aspects of the deployment process:

1. **Environment Configuration** - `bin/configure-forge.sh`
2. **Deployment Execution** - `bin/deploy-forge.sh`
3. **Health Monitoring** - `bin/health-check.sh`

These scripts work together to provide a robust, reliable deployment process with built-in safeguards and error handling.

## Environment Configuration (`configure-forge.sh`)

This script handles the initial setup and configuration of Laravel Forge environments.

### Features

- Securely stores Forge API credentials using OpenSSL encryption
- Configures multiple environments (development, staging, production)
- Stores environment-specific settings (domains, database credentials, etc.)
- Generates appropriate `.env` files for each environment

### Usage

```bash
./bin/configure-forge.sh
```

The script will prompt for:
- Laravel Forge API token
- Server ID for each environment
- Domain names
- Database configuration
- Environment-specific settings

### Configuration Storage

All configuration is stored in the `.forge-config` directory:
- `.forge-config/credentials.enc` - Encrypted Forge API token
- `.forge-config/environments/production.json` - Production environment configuration
- `.forge-config/environments/staging.json` - Staging environment configuration
- `.forge-config/environments/development.json` - Development environment configuration

## Deployment Execution (`deploy-forge.sh`)

This script handles the actual deployment process to Laravel Forge servers.

### Features

- Zero-downtime deployment
- Environment-specific deployment steps
- Pre-deployment snapshot for rollback capability
- Comprehensive deployment monitoring
- Post-deployment health checks
- Detailed progress reporting and error handling

### Usage

```bash
./bin/deploy-forge.sh [environment] [branch]

# Examples:
./bin/deploy-forge.sh production main
./bin/deploy-forge.sh staging develop
```

### Deployment Process

The script follows these steps:
1. Load credentials and environment configuration
2. Take pre-deployment snapshot for potential rollback
3. Update environment variables in Forge
4. Configure deployment script with environment-specific settings
5. Update Git branch if needed
6. Configure queue workers (for staging/production)
7. Trigger deployment and monitor progress
8. Perform post-deployment health checks
9. Report deployment status and metrics

### Rollback Capability

In case of deployment failure, the script can automatically roll back to the previous state:

```bash
# Trigger manual rollback
./bin/deploy-forge.sh [environment] [previous-branch] rollback
```

## Health Monitoring (`health-check.sh`)

This script performs comprehensive health checks on the deployed application.

### Features

- HTTP status check
- Database connection verification
- Redis connection testing
- Queue processing check
- Storage permissions validation
- SSL certificate verification
- Performance metrics
- Disk usage monitoring

### Usage

```bash
./bin/health-check.sh [url] [timeout] [app_path]

# Example:
./bin/health-check.sh https://your-domain.com 5 /home/forge/your-domain.com
```

### Health Check Process

The script performs the following checks:
1. Verify HTTP response status
2. Test database connection
3. Check Redis connectivity
4. Verify queue worker operation
5. Test storage directory permissions
6. Validate SSL certificate and expiration
7. Measure application response time
8. Monitor disk space usage

## Environment-Specific Configurations

The deployment system handles different settings for each environment:

