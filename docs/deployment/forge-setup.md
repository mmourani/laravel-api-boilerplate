# Laravel Forge Setup Guide

This document provides step-by-step instructions for setting up and deploying this SaaS Boilerplate using Laravel Forge.

## Table of Contents

- [Prerequisites](#prerequisites)
- [Initial Server Setup](#initial-server-setup)
- [Site Configuration](#site-configuration)
- [Database Setup](#database-setup)
- [Environment Configuration](#environment-configuration)
- [Deployment Configuration](#deployment-configuration)
- [Queue Workers](#queue-workers)
- [SSL Configuration](#ssl-configuration)
- [Scheduled Tasks](#scheduled-tasks)
- [Monitoring](#monitoring)
- [Backups](#backups)
- [Security Best Practices](#security-best-practices)
- [Troubleshooting](#troubleshooting)

## Prerequisites

Before proceeding, ensure you have:

1. A [Laravel Forge](https://forge.laravel.com) account
2. A cloud server provider account (DigitalOcean, AWS, Linode, etc.)
3. A domain name for your application
4. A GitHub/GitLab/Bitbucket account with your project repository

## Initial Server Setup

1. **Create a new server**:
   - Log in to your Laravel Forge account
   - Click "Create Server"
   - Select your cloud provider (DigitalOcean, AWS, etc.)
   - Choose a region close to your target audience
   - Select PHP 8.2
   - Choose appropriate server size (recommended: at least 2GB RAM)
   - Set a server name (e.g., `saas-production`)
   - Click "Create Server"

2. **Wait for provisioning to complete**:
   - This process typically takes 5-10 minutes
   - Forge will install all necessary software (Nginx, PHP, MySQL, Redis, etc.)

## Site Configuration

1. **Create a new site**:
   - Navigate to your server in Forge
   - Click "New Site"
   - Enter your domain (e.g., `app.example.com`)
   - Select PHP 8.2
   - Choose whether to install a database (recommended)
   - Click "Add Site"

2. **Configure repository**:
   - Click "Git Repository" in your site's management panel
   - Select GitHub/GitLab/Bitbucket
   - Select your repository
   - Select the branch to deploy (usually `main` or `master`)
   - Click "Install Repository"

3. **Configure site directories**:
   - Public directory should be `/public`
   - Ensure the web directory permissions are set correctly

## Database Setup

1. **Create a database**:
   - Navigate to "Database" in your server's management panel
   - Click "Create Database"
   - Set a database name (e.g., `saas_production`)
   - Optionally create a dedicated database user for better security

2. **Configure database connection**:
   - Update the `.env` file with your database credentials (Forge will do this automatically if you create the database through Forge)

## Environment Configuration

1. **Configure environment variables**:
   - Navigate to your site's management panel
   - Click "Environment"
   - Add or update the following variables:
     ```
     APP_ENV=production
     APP_DEBUG=false
     APP_URL=https://your-domain.com
     
     DB_CONNECTION=mysql
     DB_HOST=127.0.0.1
     DB_PORT=3306
     DB_DATABASE=your_database_name
     DB_USERNAME=your_database_user
     DB_PASSWORD=your_database_password
     
     CACHE_DRIVER=redis
     SESSION_DRIVER=redis
     QUEUE_CONNECTION=redis
     
     REDIS_HOST=127.0.0.1
     REDIS_PASSWORD=null
     REDIS_PORT=6379
     
     MAIL_MAILER=smtp
     MAIL_HOST=your-mail-server
     MAIL_PORT=587
     MAIL_USERNAME=your-mail-username
     MAIL_PASSWORD=your-mail-password
     MAIL_ENCRYPTION=tls
     MAIL_FROM_ADDRESS=no-reply@your-domain.com
     MAIL_FROM_NAME="${APP_NAME}"
     ```
   - Click "Save Environment Variables"

## Deployment Configuration

1. **Configure deployment script**:
   - Navigate to your site's management panel
   - Click "Deploy"
   - Update the deployment script with our zero-downtime deployment script (see `deploy-script.sh`)
   - Click "Save Deployment Script"

2. **Enable Quick Deploy** (optional):
   - Toggle "Quick Deploy"
   - This will automatically deploy when you push to your selected branch

3. **Deploy manually for the first time**:
   - Click "Deploy Now"
   - Monitor the deployment process in the "Deployment History" section

## Queue Workers

1. **Configure queue workers**:
   - Navigate to your site's management panel
   - Click "Queue"
   - Configure the following:
     - Connection: `redis`
     - Queue: `default,emails,notifications`
     - Processes: `2` (adjust based on your server resources)
     - Sleep: `10`
     - Timeout: `60`
     - Tries: `3`
   - Click "Add Worker"

2. **Manually configure Supervisor** (optional):
   - For more advanced configurations, see our `supervisor-config.conf` template

## SSL Configuration

1. **Install SSL certificate**:
   - Navigate to your site's management panel
   - Click "SSL"
   - Select "Let's Encrypt"
   - Enter your email address
   - Select the domains to secure
   - Click "Obtain Certificate"

2. **Enable HTTPS-only access**:
   - After the certificate is installed, toggle "Activate" to enable HTTPS
   - Enable HSTS for enhanced security (recommended for production)

## Scheduled Tasks

1. **Configure Laravel Scheduler**:
   - Navigate to your server management panel
   - Click "Scheduler"
   - Click "New Schedule"
   - Command: `php /home/forge/your-site.com/artisan schedule:run`
   - User: `forge`
   - Frequency: `Every Minute`
   - Click "Create Schedule"

## Monitoring

1. **Setup basic monitoring**:
   - Navigate to your server management panel
   - Click "Monitoring"
   - Toggle "Enable Monitoring" to enable monitoring for CPU, memory, and disk usage

2. **Configure external monitoring** (recommended):
   - Consider using services like New Relic, Sentry, or Laravel Telescope for more comprehensive monitoring

## Backups

1. **Database backups**:
   - Navigate to your server management panel
   - Click "Backups"
   - Configure daily database backups
   - Specify a backup destination (S3, DigitalOcean Spaces, etc.)

2. **Application backups**:
   - Set up a scheduled task to run your backup script
   - See our backup documentation for more details

## Security Best Practices

1. **Firewall configuration**:
   - By default, Forge configures UFW to allow only SSH, HTTP, and HTTPS traffic
   - Additional ports should only be opened if necessary

2. **SSH hardening**:
   - Disable password authentication (Forge does this by default)
   - Use SSH keys only

3. **Database security**:
   - Use a strong password
   - Limit database user permissions
   - Consider using a separate database server for production

4. **Application secrets**:
   - Store all sensitive information in environment variables
   - Regularly rotate API keys and credentials

5. **Regular updates**:
   - Keep all software updated
   - Periodically review and update dependencies

## Troubleshooting

1. **Deployment failures**:
   - Check the deployment log for errors
   - Ensure your application can be built and deployed locally
   - Verify your deployment script is correct

2. **Database connection issues**:
   - Verify database credentials in your `.env` file
   - Check database user permissions
   - Ensure the database server is running

3. **Queue worker issues**:
   - Check the queue worker logs
   - Ensure Redis is properly configured
   - Restart the queue worker if necessary

4. **SSL certificate issues**:
   - Ensure your domain's DNS is configured correctly
   - Verify domain ownership
   - Check certificate expiration dates

For more detailed troubleshooting, consult the Laravel and Forge documentation or contact support.

