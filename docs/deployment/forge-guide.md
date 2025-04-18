# Laravel Forge Deployment Guide

This document provides comprehensive instructions for setting up, configuring, and deploying the SaaS Boilerplate using Laravel Forge, with a focus on proper staging environment configuration and zero-downtime deployments.

## Table of Contents

- [Prerequisites](#prerequisites)
- [Multi-Environment Strategy](#multi-environment-strategy)
- [Initial Server Setup](#initial-server-setup)
- [Staging Server Configuration](#staging-server-configuration)
- [Site Configuration](#site-configuration)
- [Database Setup](#database-setup)
- [Environment Configuration](#environment-configuration)
- [Zero-Downtime Deployment](#zero-downtime-deployment)
- [Queue Workers](#queue-workers)
- [SSL Configuration](#ssl-configuration)
- [Scheduled Tasks](#scheduled-tasks)
- [Monitoring and Maintenance](#monitoring-and-maintenance)
- [Backup and Disaster Recovery](#backup-and-disaster-recovery)
- [Rollback Procedures](#rollback-procedures)
- [CI/CD Integration](#cicd-integration)
- [Security Best Practices](#security-best-practices)
- [Troubleshooting](#troubleshooting)
## Prerequisites

Before proceeding, ensure you have:

1. A [Laravel Forge](https://forge.laravel.com) account
2. A cloud server provider account (DigitalOcean, AWS, Linode, etc.)
3. Domain names for your environments (e.g., `app.example.com`, `staging.example.com`)
4. A GitHub/GitLab/Bitbucket account with your project repository
5. SSH key access configured for secure connections

## Multi-Environment Strategy

This boilerplate is designed to support multiple deployment environments:

| Environment | Purpose | Branch | Domain Example |
|-------------|---------|--------|----------------|
| Development | Local development and testing | Various feature branches | localhost:8000 |
| Staging | Pre-release testing and validation | `develop` | staging.example.com |
| Production | Live application | `main` | app.example.com |

The deployment strategy follows these principles:

1. **Environment Isolation**: Each environment has its own server, database, and configuration
2. **Progressive Promotion**: Code moves from development → staging → production
3. **Environment Parity**: Staging closely matches production configuration
4. **Zero-Downtime Updates**: Deployments occur without service interruption

The deployment scripts in this boilerplate automate the process of maintaining these environments.
4. **SSL certificate issues**:
   - Ensure your domain's DNS is configured correctly
   - Verify domain ownership
   - Check certificate expiration dates

For more detailed troubleshooting, consult the Laravel and Forge documentation or contact support.

## Staging Server Configuration

Setting up a properly configured staging environment is crucial for testing before production deployment.

### Creating a Staging Server

1. **Provision a new server in Forge**:
   - Follow the same steps as for production, but name it `saas-staging`
   - Select the same provider and region as your production server
   - Use the same PHP version as production (PHP 8.2+)
   - Choose a smaller server size if cost is a concern (2GB RAM minimum)

2. **Configure staging domain**:
   - Create a new site with domain `staging.example.com`
   - Point a subdomain (e.g., `staging.example.com`) to this server's IP address
   - Follow the same site setup steps as for production

3. **Configure staging database**:
   - Create a new database for staging (e.g., `saas_staging`)
   - Best practice: Use a different database user than production

### Staging-Specific Configuration

1. **Adjust environment variables for staging**:
   ```
   APP_ENV=staging
   APP_DEBUG=true
   APP_URL=https://staging.example.com
   
   MAIL_FROM_ADDRESS=no-reply-staging@example.com
   ```

2. **Important staging considerations**:
   - Configure mail settings to prevent accidental emails to real users
   - Add a visual indicator that this is a staging environment (e.g., banner)
   - Consider seeding the database with test data
   - Enable more verbose error reporting and logging

3. **Running the staging configuration script**:
   ```bash
   ./bin/deploy-forge.sh staging develop
   ```

   This script will:
   - Automatically configure the staging environment
   - Set up the proper Git branch (`develop`)
   - Configure deployment scripts with staging-specific settings
   - Set up database seeding during migration

## Zero-Downtime Deployment

This boilerplate uses a sophisticated zero-downtime deployment process automated through the `deploy-forge.sh` script.

### How Zero-Downtime Deployment Works

1. **Application placed in maintenance mode with retry parameters**:
   ```php
   php artisan down --refresh=15 --retry=60
   ```
   This shows a maintenance page but retries connections after 15 seconds, with a 60-second retry time.

2. **Deployment process**:
   - Code is pulled from the repository
   - Composer dependencies are installed (excluding dev in production)
   - Database migrations run without interrupting service
   - Asset compilation and optimization
   - Cache clearing and config caching
   - Queue workers restarted
   - Application brought back online

3. **Health check verification**:
   - Automated health checks ensure the application is responding correctly
   - Multiple retry attempts with a configurable delay
   - Automatic rollback if health checks fail

### Using the Deployment Script

1. **Basic deployment command**:
   ```bash
   ./bin/deploy-forge.sh [environment] [branch]
   
   # Examples:
   ./bin/deploy-forge.sh staging develop
   ./bin/deploy-forge.sh production main
   ```

2. **Deployment script features**:
   - **Snapshots**: Creates deployment snapshots for potential rollbacks
   - **Environment detection**: Customizes deployment based on target environment
   - **Health checks**: Verifies successful deployment
   - **Logging**: Comprehensive logging of deployment steps
   - **Error handling**: Graceful handling of deployment failures

### Customizing the Deployment Script

The deployment script checks for environment-specific configurations:

```bash
case "$ENVIRONMENT" in
    development)
        # Development specific settings
        deploy_script=${deploy_script//'composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev'/'composer install --no-interaction --prefer-dist'}
        ;;
    staging)
        # Staging specific settings
        deploy_script=${deploy_script//'php artisan migrate --force'/'php artisan migrate --force --seed'}
        ;;
    production)
        # Production specific settings
        deploy_script=${deploy_script//'php artisan down --refresh=15'/'php artisan down --refresh=15 --retry=60'}
        ;;
esac
```

You can modify these environment-specific settings in the `deploy-forge.sh` script.

## Monitoring and Maintenance

Properly monitoring your application ensures reliability and performance.

### Server Monitoring

1. **Basic Forge monitoring**:
   - Navigate to your server in Forge → Monitoring
   - Enable monitoring for CPU, memory, and disk usage
   - Configure notification thresholds
   - Set up notification channels (email, Slack)

2. **Enhanced monitoring with third-party services**:
   - [New Relic](https://newrelic.com/): Application performance monitoring
   - [Datadog](https://www.datadoghq.com/): Infrastructure and application monitoring
   - [Sentry](https://sentry.io/): Error tracking and performance monitoring

3. **Log management**:
   - Configure log rotation in Forge (Server → Logs)
   - Consider a log aggregation service like [Papertrail](https://www.papertrail.com/)
   - Set up log alerts for critical errors

### Maintenance Procedures

1. **Scheduled maintenance window best practices**:
   - Announce maintenance windows in advance
   - Choose off-peak hours for major updates
   - Use the zero-downtime deployment script to minimize disruption

2. **Regular maintenance tasks**:
   ```bash
   # Update system packages
   apt update && apt upgrade -y
   
   # Clear Laravel caches
   php artisan cache:clear
   php artisan config:clear
   php artisan view:clear
   
   # Optimize after updates
   php artisan optimize
   
   # Restart queue workers
   php artisan queue:restart
   ```

3. **Database maintenance**:
   - Regular backups (automated through Forge)
   - Periodic index optimization
   - Monitor query performance with Laravel Telescope

4. **Security updates**:
   - Subscribe to Laravel security announcements
   - Implement automatic security updates for system packages
   - Regular vulnerability scanning

## Backup and Disaster Recovery

A comprehensive backup strategy is essential for data protection.

### Database Backups

1. **Configure Forge database backups**:
   - Navigate to your server in Forge → Backups
   - Enable daily database backups
   - Configure remote storage (S3, DigitalOcean Spaces, etc.)
   - Set backup retention period (e.g., 30 days)

2. **Manual backup script**:
   ```bash
   # Create a database backup
   mysqldump -u forge -p your_database > backup-$(date +%Y%m%d).sql
   
   # Compress the backup
   gzip backup-$(date +%Y%m%d).sql
   
   # Upload to S3 (if configured)
   aws s3 cp backup-$(date +%Y%m%d).sql.gz s3://your-bucket/backups/
   ```

### Application Backups

1. **What to back up**:
   - `.env` files
   - User uploaded files
   - Custom configuration files
   - Deployment scripts

2. **Automated backup script**:
   See `backup-config.sh` for a comprehensive backup script that:
   - Backs up the database
   - Backs up application files
   - Compresses and encrypts backups
   - Uploads to remote storage
   - Rotates old backups

### Disaster Recovery Plan

1. **Recovery Time Objective (RTO)**:
   - Define how quickly you need to recover (e.g., 1 hour)
   - Test recovery procedures regularly

2. **Recovery procedure**:
   - Provision a new server in Forge
   - Restore the latest database backup
   - Deploy the application code
   - Restore environment configuration
   - Verify functionality with health checks

## Rollback Procedures

Despite careful testing, deployments may sometimes need to be rolled back.

### Automated Rollbacks

The `deploy-forge.sh` script includes built-in rollback capabilities:

1. **How deployment snapshots work**:
   - Before each deployment, a snapshot is created of:
     - Current branch
     - Deployment script
     - Environment variables
     - Site configuration
   - Snapshots are stored in `.forge-config/backups/`

2. **Triggering an automated rollback**:
   ```bash
   ./bin/deploy-forge.sh [environment] [previous-branch] rollback
   
   # Example:
   ./bin/deploy-forge.sh production main rollback
   ```

3. **What happens during rollback**:
   - Previous deployment script is restored
   - Previous environment variables are restored
   - Previous branch is checked out
   - Deployment is triggered with original configuration

### Manual Rollback Procedure

If the automated rollback fails:

1. **Restore the database** (if schema changes occurred):
   ```bash
   # Connect to the server
   ssh forge@your-server.com
   
   # Restore database from backup
   mysql -u forge -p your_database < backup-file.sql
   ```

2. **Revert to previous code version**:
   - In Forge, change the site's deployment branch back to the known-good commit
   - Deploy manually through the Forge UI

3. **Restore environment variables**:
   - If environment variables were changed, restore them from backup

## CI/CD Integration

Automate testing and deployment with GitHub Actions integration.

### GitHub Actions Workflow

1. **Testing workflow** (`.github/workflows/tests.yml`):
   - Runs on every push and pull request
   - Sets up PHP environment
   - Configures SQLite database for testing
   - Installs dependencies
   - Runs PHPUnit tests
   - Reports code coverage

2. **Automated deployment workflow**:
   ```yaml
   name: Deploy Application
   
   on:
     push:
       branches: [develop, main]
   
   jobs:
     deploy:
       runs-on: ubuntu-latest
       steps:
         - name: Checkout code
           uses: actions/checkout@v3
           
         - name: Setup SSH
           uses: webfactory/ssh-agent@v0.7.0
           with:
             ssh-private-key: ${{ secrets.FORGE_SSH_KEY }}
             
         - name: Deploy to Staging
           if: github.ref == 'refs/heads/develop'
           run: |
             ssh forge@your-staging-server.com "cd /home/forge/staging.example.com && git pull origin develop && composer install && php artisan migrate"
             
         - name: Deploy to Production
           if: github.ref == 'refs/heads/main'
           run: |
             ./bin/deploy-forge.sh production main
   ```

### Forge Deployment Hooks

1. **Setting up Forge deployment hooks**:
   - Navigate to your site in Forge → Deploy
   - Toggle "Quick Deploy" to enable automatic deployments
   - Copy the deployment webhook URL

2. **Configure GitHub to trigger deployments**:
   - Go to your GitHub repository → Settings → Webhooks
   - Add webhook with the Forge URL
   - Select "Just the push event"

### Deployment Strategy

1. **Feature branch workflow**:
   - Develop features in dedicated branches
   - Merge to `develop` for staging deployment
   - After staging validation, merge to `main` for production

2. **Promoting code between environments**:
   ```bash
   # After testing in staging, merge to main
   git checkout develop
   git pull origin develop
   git checkout main
   git merge develop
   git push origin main
   ```

3. **Release tagging**:
   - Tag stable releases for future reference
   - Include version numbers
