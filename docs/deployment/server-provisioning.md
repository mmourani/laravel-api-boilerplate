# Server Provisioning Guide

This guide outlines the requirements and steps for provisioning a server for the SaaS Boilerplate application.

## Server Requirements

### Recommended Hardware Specifications

- **CPU**: 2+ cores
- **RAM**: 4GB minimum (8GB recommended for production)
- **Storage**: 30GB SSD minimum (100GB+ recommended for production)
- **Network**: 1Gbps connection

### Supported Cloud Providers

- DigitalOcean (Recommended)
- AWS EC2
- Google Cloud Compute Engine
- Linode
- Vultr
- Hetzner Cloud

### Operating System

- Ubuntu 22.04 LTS (Recommended)
- Debian 11+
- CentOS 8+

## Manual Provisioning Steps

If you're not using Laravel Forge, follow these steps to provision your server manually.

### 1. Initial Server Setup

```bash
# Update system packages
apt update && apt upgrade -y

# Install essential packages
apt install -y curl git unzip supervisor

# Set correct timezone
timedatectl set-timezone UTC

# Setup swap space (if RAM is less than 4GB)
fallocate -l 1G /swapfile
chmod 600 /swapfile
mkswap /swapfile
swapon /swapfile
echo '/swapfile none swap sw 0 0' >> /etc/fstab
```

### 2. Create Deploy User

```bash
# Create a deploy user
adduser deploy
usermod -aG sudo deploy

# Setup SSH for the deploy user
mkdir -p /home/deploy/.ssh
chmod 700 /home/deploy/.ssh
```

Copy your public SSH key to `/home/deploy/.ssh/authorized_keys` and set the correct permissions:

```bash
chmod 600 /home/deploy/.ssh/authorized_keys
chown -R deploy:deploy /home/deploy/.ssh
```

### 3. Install Nginx

```bash
apt install -y nginx
systemctl enable nginx
systemctl start nginx

# Allow Nginx through the firewall
ufw allow 'Nginx Full'
```

### 4. Install PHP 8.2

```bash
# Add repository for PHP 8.2
add-apt-repository -y ppa:ondrej/php
apt update

# Install PHP and required extensions
apt install -y php8.2-fpm php8.2-cli php8.2-common php8.2-mysql php8.2-zip php8.2-gd php8.2-mbstring php8.2-curl php8.2-xml php8.2-bcmath php8.2-intl php8.2-redis
```

### 5. Install Composer

```bash
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
```

### 6. Install MySQL

```bash
apt install -y mysql-server

# Secure MySQL installation
mysql_secure_installation
```

Create a database and user for the application:

```sql
CREATE DATABASE saas_app;
CREATE USER 'saas_user'@'localhost' IDENTIFIED BY 'your-strong-password';
GRANT ALL PRIVILEGES ON saas_app.* TO 'saas_user'@'localhost';
FLUSH PRIVILEGES;
```

### 7. Install Redis

```bash
apt install -y redis-server

# Enable Redis to start on boot
systemctl enable redis-server
```

### 8. Configure PHP-FPM

Edit `/etc/php/8.2/fpm/php.ini` and set the following values:

```ini
memory_limit = 256M
upload_max_filesize = 64M
post_max_size = 64M
max_execution_time = 60
```

Restart PHP-FPM:

```bash
systemctl restart php8.2-fpm
```

### 9. Configure Nginx for Laravel

Create a new Nginx server block in `/etc/nginx/sites-available/your-domain.conf`:

```nginx
# Use the configuration template from nginx-config.conf
```

Enable the site:

```bash
ln -s /etc/nginx/sites-available/your-domain.conf /etc/nginx/sites-enabled/
nginx -t  # Test the configuration
systemctl reload nginx
```

### 10. Configure Firewall

```bash
ufw allow ssh
ufw allow http
ufw allow https
ufw enable
```

### 11. Setup SSL with Certbot

```bash
apt install -y certbot python3-certbot-nginx
certbot --nginx -d your-domain.com -d www.your-domain.com
```

### 12. Configure Supervisor for Queue Workers

Create a supervisor configuration file at `/etc/supervisor/conf.d/laravel-worker.conf`:

```
# Use the configuration template from supervisor-config.conf
```

Start the worker:

```bash
supervisorctl reread
supervisorctl update
supervisorctl start laravel-workers:*
```

### 13. Setup Cron for Scheduled Tasks

Edit the crontab for the deploy user:

```bash
crontab -e -u deploy
```

Add the Laravel scheduler:

```
* * * * * cd /var/www/your-app && php artisan schedule:run >> /dev/null 2>&1
```

## Scaling Recommendations

### Vertical Scaling (Recommended for getting started)

Upgrade your server resources as needed:

- Increase CPU cores
- Add more RAM
- Expand disk space

### Horizontal Scaling (For high-traffic applications)

1. **Load Balancer Setup**:
   - Set up a load balancer (e.g., Nginx, HAProxy, or cloud provider's load balancer)
   - Configure multiple application servers behind the load balancer

2. **Database Scaling**:
   - Set up database replication (Master-Slave)
   - Consider using a managed database service

3. **Caching Layer**:
   - Implement Redis or Memcached for caching
   - Consider a distributed cache setup for larger applications

4. **File Storage**:
   - Use a distributed file system or cloud storage (S3, Spaces)
   - Configure the application to use an external storage driver

## AWS-Specific Configuration

If using AWS, consider the following services:

- **EC2**: For application servers
- **RDS**: For managed MySQL/PostgreSQL database
- **ElastiCache**: For Redis caching
- **S3**: For file storage
- **CloudFront**: For CDN
- **Route53**: For DNS management
- **Certificate Manager**: For SSL certificates
- **ELB/ALB**: For load balancing

## DigitalOcean-Specific Configuration

If using DigitalOcean, consider:

- **Droplets**: For application servers
- **Managed Databases**: For MySQL/PostgreSQL
- **Managed Redis**: For caching
- **Spaces**: For object storage
- **Load Balancers**: For distributing traffic
- **DNS**: For domain management

## Security Best Practices

1. **Keep software updated**:
   ```bash
   apt update && apt upgrade -y
   ```

2. **Use SSH key authentication only**:
   ```bash
   # Disable password authentication
   sed -i 's/PasswordAuthentication yes/PasswordAuthentication no/' /etc/ssh/sshd_config
   systemctl restart sshd
   ```

3. **Install and configure fail2ban**:
   ```bash
   apt install -y fail2ban
   cp /etc/fail2ban/jail.conf /etc/fail2ban/jail.local
   systemctl enable fail2ban
   systemctl start fail2ban
   ```

4. **Regular security audits**:
   ```bash
   # Install security audit tools
   apt install -y rkhunter lynis
   ```

5. **Setup automated security updates**:
   ```bash
   apt install -y unattended-upgrades
   dpkg-reconfigure --priority=low unattended-upgrades
   ```

## Monitoring Recommendations

- See the separate `monitoring.md` document for detailed setup instructions
- Consider using services like New Relic, Datadog, or Prometheus for comprehensive monitoring

## Backup Strategy

- See the separate `backup-config.sh` script for automated backup configuration
- Configure regular database backups to external storage
- Set up application file backups as needed

Remember to replace placeholder values like `your-domain.com` and `your-strong-password` with your actual values.

