#!/bin/bash
#
# Forge Initial Server Setup Script
# This script helps automate the process of setting up a Laravel Forge server
# by adding custom configurations beyond what Forge provides by default.
#
# Usage: ./setup-forge.sh [server_ip] [ssh_key_path]
# Example: ./setup-forge.sh 123.456.789.101 ~/.ssh/forge_rsa
#

# Check if correct arguments were provided
if [ "$#" -lt 1 ]; then
    echo "Usage: $0 [server_ip] [ssh_key_path]"
    echo "Example: $0 123.456.789.101 ~/.ssh/forge_rsa"
    exit 1
fi

SERVER_IP="$1"
SSH_KEY="${2:-~/.ssh/id_rsa}"
FORGE_USER="forge"
PROJECT_NAME="laravel-saas"
SSH_PORT=22
ROOT_DOMAIN="your-domain.com"

echo "Starting Forge server setup for $SERVER_IP..."

# Function to run commands on the remote server
run_ssh_command() {
    ssh -i "$SSH_KEY" -p $SSH_PORT "$FORGE_USER@$SERVER_IP" "$1"
}

# Check if SSH connection works
echo "Testing SSH connection..."
if ! run_ssh_command "echo 'SSH connection successful'"; then
    echo "Error: Cannot connect to server. Please check your IP and SSH key."
    exit 1
fi

# Create project directories
echo "Creating project directories..."
run_ssh_command "mkdir -p ~/$PROJECT_NAME/storage/logs"
run_ssh_command "mkdir -p ~/$PROJECT_NAME/bootstrap/cache"

# Configure security packages
echo "Configuring additional security packages..."
run_ssh_command "sudo apt-get update && sudo apt-get install -y fail2ban ufw"
run_ssh_command "sudo ufw default deny incoming"
run_ssh_command "sudo ufw default allow outgoing"
run_ssh_command "sudo ufw allow ssh"
run_ssh_command "sudo ufw allow http"
run_ssh_command "sudo ufw allow https"
run_ssh_command "echo 'y' | sudo ufw enable"

# Configure PHP settings
echo "Configuring PHP settings..."
run_ssh_command "echo 'memory_limit = 512M' | sudo tee -a /etc/php/8.2/fpm/conf.d/99-custom.ini"
run_ssh_command "echo 'upload_max_filesize = 128M' | sudo tee -a /etc/php/8.2/fpm/conf.d/99-custom.ini"
run_ssh_command "echo 'post_max_size = 128M' | sudo tee -a /etc/php/8.2/fpm/conf.d/99-custom.ini"
run_ssh_command "echo 'max_execution_time = 120' | sudo tee -a /etc/php/8.2/fpm/conf.d/99-custom.ini"
run_ssh_command "sudo systemctl restart php8.2-fpm"

# Configure MySQL settings
echo "Configuring MySQL settings..."
run_ssh_command "echo '[mysqld]' | sudo tee -a /etc/mysql/conf.d/custom.cnf"
run_ssh_command "echo 'innodb_buffer_pool_size = 256M' | sudo tee -a /etc/mysql/conf.d/custom.cnf"
run_ssh_command "echo 'innodb_log_file_size = 64M' | sudo tee -a /etc/mysql/conf.d/custom.cnf"
run_ssh_command "echo 'max_connections = 200' | sudo tee -a /etc/mysql/conf.d/custom.cnf"
run_ssh_command "sudo systemctl restart mysql"

# Configure Redis settings
echo "Configuring Redis settings..."
run_ssh_command "echo 'maxmemory 256mb' | sudo tee -a /etc/redis/redis.conf"
run_ssh_command "echo 'maxmemory-policy allkeys-lru' | sudo tee -a /etc/redis/redis.conf"
run_ssh_command "sudo systemctl restart redis-server"

# Configure Nginx settings (custom optimizations)
echo "Configuring Nginx settings..."
run_ssh_command "sudo sed -i 's/worker_connections 768;/worker_connections 1024;/g' /etc/nginx/nginx.conf"
run_ssh_command "sudo sed -i 's/keepalive_timeout 65;/keepalive_timeout 30;/g' /etc/nginx/nginx.conf"
run_ssh_command "sudo systemctl restart nginx"

# Setup automatic security updates
echo "Setting up automatic security updates..."
run_ssh_command "sudo apt-get install -y unattended-upgrades"
run_ssh_command "sudo dpkg-reconfigure -f noninteractive unattended-upgrades"

# Create OPcache clearing script
echo "Creating OPcache clearing script..."
run_ssh_command "cat > ~/opcache-clear.php << 'EOL'
<?php
opcache_reset();
echo 'OPcache cleared successfully at ' . date('Y-m-d H:i:s');
EOL"

run_ssh_command "sudo mv ~/opcache-clear.php /var/www/html/opcache-clear.php"
run_ssh_command "sudo chown www-data:www-data /var/www/html/opcache-clear.php"

# Create a basic health check script
echo "Creating health check script..."
run_ssh_command "cat > ~/health-check.sh << 'EOL'
#!/bin/bash
curl -s -o /dev/null -w '%{http_code}' http://localhost/health
EOL"

run_ssh_command "chmod +x ~/health-check.sh"

echo "Forge server setup completed successfully."
echo ""
echo "Next steps:"
echo "1. Login to Forge dashboard and create a new site"
echo "2. Install Git repository for your application"
echo "3. Configure environment variables"
echo "4. Setup SSL certificate"
echo "5. Configure queue workers"
echo ""
echo "Server IP: $SERVER_IP"
echo ""

exit 0

