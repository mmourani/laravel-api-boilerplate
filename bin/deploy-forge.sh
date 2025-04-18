#!/bin/bash
#
# Laravel Forge Deployment Script
# This script deploys the application to Laravel Forge for the specified environment.
#
# Usage: ./deploy-forge.sh [environment] [branch]
# Example: ./deploy-forge.sh staging develop
#

# Exit on error
set -e

# Color and formatting settings
BOLD=$(tput bold)
NORMAL=$(tput sgr0)
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration directories
CONFIG_DIR=".forge-config"
CREDENTIALS_FILE="$CONFIG_DIR/credentials.enc"
ENV_DIR="$CONFIG_DIR/environments"
BACKUP_DIR="$CONFIG_DIR/backups"

# Default values
DEFAULT_BRANCH="main"
ENVIRONMENT=${1:-production}
BRANCH=${2:-$DEFAULT_BRANCH}
DEPLOYMENT_ID=$(date +%Y%m%d%H%M%S)
SNAPSHOT_FILE="$BACKUP_DIR/$ENVIRONMENT-$DEPLOYMENT_ID.json"

# Create backup directory if it doesn't exist
mkdir -p "$BACKUP_DIR"

# Function to print section headers
print_header() {
    echo -e "\n${BOLD}${BLUE}$1${NC}${NORMAL}\n"
}

# Function to print success messages
print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

# Function to print warning messages
print_warning() {
    echo -e "${YELLOW}⚠ $1${NC}"
}

# Function to print error messages
print_error() {
    echo -e "${RED}✗ $1${NC}"
    return 1
}

# Function to print step indicators
print_step() {
    local step=$1
    local total=$2
    local message=$3
    echo -e "${BOLD}[${step}/${total}]${NORMAL} ${message}"
}

# Function to decrypt data
decrypt_data() {
    local input_file="$1"
    
    if [ ! -f "$CONFIG_DIR/passphrase" ]; then
        print_error "Passphrase file not found. Cannot decrypt data."
        exit 1
    fi
    
    openssl enc -aes-256-cbc -d -salt -pbkdf2 -in "$input_file" -pass file:"$CONFIG_DIR/passphrase"
}

# Function to perform API requests to Forge
forge_api_request() {
    local method=$1
    local endpoint=$2
    local data=${3:-"{}"}
    local token=$FORGE_TOKEN
    
    curl -s -X "$method" \
        -H "Authorization: Bearer $token" \
        -H "Accept: application/json" \
        -H "Content-Type: application/json" \
        --data "$data" \
        "https://forge.laravel.com/api/v1$endpoint"
}

# Function to handle errors and clean up
handle_error() {
    print_error "An error occurred during deployment. See the error message above."
    
    # Ask if rollback should be performed
    if [ -f "$SNAPSHOT_FILE" ]; then
        read -p "Would you like to rollback to the previous state? [Y/n]: " do_rollback
        do_rollback=${do_rollback:-Y}
        
        if [[ "$do_rollback" =~ ^[Yy]$ ]]; then
            perform_rollback
        fi
    fi
    
    exit 1
}

# Function to take a snapshot before deployment
take_snapshot() {
    print_step 2 8 "Taking snapshot of current state..."
    
    # Get site ID
    local site_id=$(forge_api_request "GET" "/servers/$SERVER_ID/sites" | jq -r --arg domain "$DOMAIN" '.sites[] | select(.name == $domain) | .id')
    
    if [ -z "$site_id" ] || [ "$site_id" == "null" ]; then
        print_error "Site not found for domain: $DOMAIN"
        exit 1
    fi
    
    # Get current deployment script
    local deployment_script=$(forge_api_request "GET" "/servers/$SERVER_ID/sites/$site_id/deployment/script")
    
    # Get environment variables
    local env_vars=$(forge_api_request "GET" "/servers/$SERVER_ID/sites/$site_id/environment")
    
    # Get deployment status
    local deployment_status=$(forge_api_request "GET" "/servers/$SERVER_ID/sites/$site_id/deployment/status")
    
    # Create snapshot file
    cat > "$SNAPSHOT_FILE" << EOF
{
    "environment": "$ENVIRONMENT",
    "server_id": "$SERVER_ID",
    "site_id": "$site_id",
    "domain": "$DOMAIN",
    "branch": "$(forge_api_request "GET" "/servers/$SERVER_ID/sites/$site_id/git" | jq -r '.branch')",
    "deployment_script": $(echo "$deployment_script" | jq '.script'),
    "environment_variables": $(echo "$env_vars" | jq '.content'),
    "deployment_status": $(echo "$deployment_status" | jq '.')
}
EOF
    
    print_success "Snapshot saved to $SNAPSHOT_FILE"
}

# Function to perform rollback in case of deployment failure
perform_rollback() {
    print_header "Performing rollback..."
    
    if [ ! -f "$SNAPSHOT_FILE" ]; then
        print_error "Snapshot file not found. Cannot perform rollback."
        return 1
    fi
    
    # Read snapshot file
    local snapshot=$(cat "$SNAPSHOT_FILE")
    local site_id=$(echo "$snapshot" | jq -r '.site_id')
    local deployment_script=$(echo "$snapshot" | jq -r '.deployment_script')
    local env_vars=$(echo "$snapshot" | jq -r '.environment_variables')
    local old_branch=$(echo "$snapshot" | jq -r '.branch')
    
    # Restore deployment script
    print_warning "Restoring deployment script..."
    forge_api_request "PUT" "/servers/$SERVER_ID/sites/$site_id/deployment/script" "{\"content\": \"$deployment_script\"}" > /dev/null
    
    # Restore environment variables
    print_warning "Restoring environment variables..."
    forge_api_request "PUT" "/servers/$SERVER_ID/sites/$site_id/environment" "{\"content\": \"$env_vars\"}" > /dev/null
    
    # Restore branch if it changed
    if [ "$old_branch" != "$BRANCH" ]; then
        print_warning "Restoring branch to $old_branch..."
        forge_api_request "PUT" "/servers/$SERVER_ID/sites/$site_id/git" "{\"branch\": \"$old_branch\"}" > /dev/null
    fi
    
    print_success "Rollback completed successfully"
}

# Function to perform health checks
perform_health_check() {
    local stage=$1
    local max_retries=5
    local retry_delay=3
    
    print_step $stage 8 "Performing health check..."
    
    # Check if we can get a 200 OK or 3xx redirect from the site
    for i in $(seq 1 $max_retries); do
        local status_code=$(curl -s -o /dev/null -w "%{http_code}" -L "https://$DOMAIN")
        
        if [[ "$status_code" =~ ^(200|3[0-9]{2})$ ]]; then
            print_success "Health check passed: Site is responding with status code $status_code"
            return 0
        else
            if [ $i -lt $max_retries ]; then
                print_warning "Health check attempt $i failed with status code $status_code. Retrying in ${retry_delay}s..."
                sleep $retry_delay
            else
                print_error "Health check failed after $max_retries attempts. Last status code: $status_code"
                return 1
            fi
        fi
    done
}

# Function to update environment variables
update_env_vars() {
    print_step 3 8 "Updating environment variables..."
    
    # Read environment file
    local env_file=".env.$ENVIRONMENT"
    if [ ! -f "$env_file" ]; then
        print_warning "Environment file not found: $env_file"
        print_warning "Generating environment file from configuration..."
        
        # Read environment configuration
        local env_config=$(cat "$ENV_CONFIG_FILE")
        
        # Extract values from configuration
        local domain=$(echo "$env_config" | jq -r '.domain')
        local db_name=$(echo "$env_config" | jq -r '.database.name')
        local db_user=$(echo "$env_config" | jq -r '.database.user')
        local db_password=$(echo "$env_config" | jq -r '.database.password')
        local app_debug=$(echo "$env_config" | jq -r '.app_settings.debug')
        local queue_driver=$(echo "$env_config" | jq -r '.app_settings.queue_driver')
        
        # Generate .env file based on template
        cp .env.example "$env_file"
        
        # Replace values in the .env file
        sed -i.bak "s#APP_ENV=.*#APP_ENV=$ENVIRONMENT#g" "$env_file"
        sed -i.bak "s#APP_DEBUG=.*#APP_DEBUG=$app_debug#g" "$env_file"
        sed -i.bak "s#APP_URL=.*#APP_URL=https://$domain#g" "$env_file"
        
        sed -i.bak "s#DB_DATABASE=.*#DB_DATABASE=$db_name#g" "$env_file"
        sed -i.bak "s#DB_USERNAME=.*#DB_USERNAME=$db_user#g" "$env_file"
        sed -i.bak "s#DB_PASSWORD=.*#DB_PASSWORD=$db_password#g" "$env_file"
        
        sed -i.bak "s#QUEUE_CONNECTION=.*#QUEUE_CONNECTION=$queue_driver#g" "$env_file"
        
        # Clean up backup files
        rm -f "$env_file.bak"
        
        print_success "Generated $env_file"
    fi
    
    # Read environment file
    local env_content=$(cat "$env_file")
    
    # Update environment variables on Forge
    forge_api_request "PUT" "/servers/$SERVER_ID/sites/$SITE_ID/environment" "{\"content\": $(echo "$env_content" | jq -Rs .)}" > /dev/null
    
    print_success "Environment variables updated successfully"
}

# Function to configure deployment script
configure_deployment_script() {
    print_step 4 8 "Configuring deployment script..."
    
    # Read deployment script template
    local deploy_script=$(cat "docs/deployment/deploy-script.sh")
    
    # Customize script for environment
    deploy_script=${deploy_script//{{project-directory}}/$DOMAIN}
    
    # Add environment-specific customizations
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
    
    # Add post-deployment health check
    deploy_script+=$'\n\n# Run health check\necho "Running post-deployment health check..."\n/home/forge/'$DOMAIN'/bin/health-check.sh https://'$DOMAIN' 10 /home/forge/'$DOMAIN
    
    # Update deployment script on Forge
    forge_api_request "PUT" "/servers/$SERVER_ID/sites/$SITE_ID/deployment/script" "{\"content\": $(echo "$deploy_script" | jq -Rs .)}" > /dev/null
    
    print_success "Deployment script configured successfully"
}

# Function to update git branch
update_git_branch() {
    print_step 5 8 "Updating Git branch to $BRANCH..."
    
    # Update branch on Forge
    forge_api_request "PUT" "/servers/$SERVER_ID/sites/$SITE_ID/git" "{\"branch\": \"$BRANCH\"}" > /dev/null
    
    print_success "Git branch updated successfully"
}

# Function to configure queue workers
configure_queue_workers() {
    print_step 6 8 "Configuring queue workers..."
    
    # Get number of queue workers from configuration
    local queue_workers=$(echo "$ENV_CONFIG" | jq -r '.queue_workers // 1')
    
    # Get current queue workers
    local current_workers=$(forge_api_request "GET" "/servers/$SERVER_ID/sites/$SITE_ID/workers")
    local worker_count=$(echo "$current_workers" | jq -r '.workers | length')
    
    if [ "$worker_count" -eq 0 ]; then
        print_warning "No queue workers found. Creating queue worker..."
        
        # Create queue worker
        forge_api_request "POST" "/servers/$SERVER_ID/sites/$SITE_ID/workers" "{
            \"connection\": \"redis\",
            \"queue\": \"default,emails,notifications\",
            \"timeout\": 60,
            \"sleep\": 10,
            \"tries\": 3,
            \"processes\": $queue_workers
        }" > /dev/null
        
        print_success "Queue worker created successfully"
    elif [ "$worker_count" -gt 0 ]; then
        print_warning "Queue workers already exist. Checking configuration..."
        
        # Get the ID of the first worker
        local worker_id=$(echo "$current_workers" | jq -r '.workers[0].id')
        
        # Update queue worker
        forge_api_request "PUT" "/servers/$SERVER_ID/sites/$SITE_ID/workers/$worker_id" "{
            \"connection\": \"redis\",
            \"queue\": \"default,emails,notifications\",
            \"timeout\": 60,
            \"sleep\": 10,
            \"tries\": 3,
            \"processes\": $queue_workers
        }" > /dev/null
        
        print_success "Queue worker updated successfully"
    fi
}

# Function to deploy the application
deploy_application() {
    print_step 7 8 "Deploying application..."
    
    # Trigger deployment on Forge
    local response=$(forge_api_request "POST" "/servers/$SERVER_ID/sites/$SITE_ID/deployment/deploy")
    local status=$(echo "$response" | jq -r '.status')
    
    if [ "$status" != "deploying" ]; then
        print_error "Failed to trigger deployment. Response: $(echo "$response" | jq -c '.')"
        return 1
    fi
    
    print_success "Deployment triggered successfully"
    
    # Monitor deployment progress
    local max_wait_time=300  # 5 minutes
    local wait_interval=10   # 10 seconds
    local elapsed_time=0
    
    echo "Monitoring deployment progress..."
    
    while [ $elapsed_time -lt $max_wait_time ]; do
        local deploy_status=$(forge_api_request "GET" "/servers/$SERVER_ID/sites/$SITE_ID/deployment/status" | jq -r '.status')
        
        case "$deploy_status" in
            "deploying")
                echo -n "."
                ;;
            "deployed")
                echo ""
                print_success "Deployment completed successfully"
                break
                ;;
            "failed")
                echo ""
                print_error "Deployment failed. Check the Forge dashboard for details."
                return 1
                ;;
            *)
                echo ""
                print_warning "Unknown deployment status: $deploy_status"
                ;;
        esac
        
        sleep $wait_interval
        elapsed_time=$((elapsed_time + wait_interval))
    done
    
    if [ $elapsed_time -ge $max_wait_time ]; then
        print_error "Deployment timed out after $max_wait_time seconds"
        return 1
    fi
    
    return 0
}

# Function to perform final checks after deployment
perform_final_checks() {
    print_step 8 8 "Performing final checks..."
    
    # Wait a bit for the application to fully initialize
    print_warning "Waiting for application to stabilize..."
    sleep 10
    
    # Perform final health check
    if ! perform_health_check 8; then
        print_error "Final health check failed"
        return 1
    fi
    
    print_success "All checks passed successfully"
    return 0
}

# Setup error handling
trap 'handle_error' ERR

# Main execution starts here
print_header "Laravel Forge Deployment - $ENVIRONMENT Environment"

# Check requirements
command -v jq >/dev/null 2>&1 || { print_error "jq is required but not installed. Please install it and try again."; exit 1; }
command -v curl >/dev/null 2>&1 || { print_error "curl is required but not installed. Please install it and try again."; exit 1; }
command -v openssl >/dev/null 2>&1 || { print_error "openssl is required but not installed. Please install it and try again."; exit 1; }

# Check if credentials file exists
if [ ! -f "$CREDENTIALS_FILE" ]; then
    print_error "Credentials file not found. Please run ./bin/configure-forge.sh first."
    exit 1
fi

# Check if environment configuration exists
ENV_CONFIG_FILE="$ENV_DIR/$ENVIRONMENT.json"
if [ ! -f "$ENV_CONFIG_FILE" ]; then
    print_error "Environment configuration not found for $ENVIRONMENT. Please run ./bin/configure-forge.sh first."
    exit 1
fi

# Decrypt API token
print_step 1 8 "Loading credentials and configuration..."
FORGE_TOKEN=$(decrypt_data "$CREDENTIALS_FILE")

# Read environment configuration
ENV_CONFIG=$(cat "$ENV_CONFIG_FILE")
SERVER_ID=$(echo "$ENV_CONFIG" | jq -r '.server_id')
DOMAIN=$(echo "$ENV_CONFIG" | jq -r '.domain')

# Get site ID
SITE_ID=$(forge_api_request "GET" "/servers/$SERVER_ID/sites" | jq -r --arg domain "$DOMAIN" '.sites[] | select(.name == $domain) | .id')

if [ -z "$SITE_ID" ] || [ "$SITE_ID" == "null" ]; then
    print_error "Site not found for domain: $DOMAIN"
    exit 1
fi

print_success "Configuration loaded successfully"
print_warning "Deploying to $ENVIRONMENT environment with domain $DOMAIN"
print_warning "Using branch: $BRANCH"
print_warning ""

# Ask for confirmation before proceeding
read -p "Continue with deployment? [Y/n]: " continue_deployment
continue_deployment=${continue_deployment:-Y}

if [[ ! "$continue_deployment" =~ ^[Yy]$ ]]; then
    print_warning "Deployment canceled"
    exit 0
fi

# Record start time
start_time=$(date +%s)

# Take snapshot of current state
take_snapshot

# Update environment variables
update_env_vars

# Configure deployment script
configure_deployment_script

# Update git branch if needed
current_branch=$(forge_api_request "GET" "/servers/$SERVER_ID/sites/$SITE_ID/git" | jq -r '.branch')
if [ "$current_branch" != "$BRANCH" ]; then
    update_git_branch
fi

# Configure queue workers if this environment needs them
if [[ "$ENVIRONMENT" == "staging" || "$ENVIRONMENT" == "production" ]]; then
    configure_queue_workers
fi

# Deploy the application
if ! deploy_application; then
    print_error "Deployment failed"
    handle_error
fi

# Perform final checks
if ! perform_final_checks; then
    print_error "Final checks failed"
    handle_error
fi

# Calculate elapsed time
end_time=$(date +%s)
elapsed_time=$((end_time - start_time))
elapsed_minutes=$((elapsed_time / 60))
elapsed_seconds=$((elapsed_time % 60))

# Show success message
print_header "Deployment Summary"
print_success "Deployment to $ENVIRONMENT completed successfully!"
print_success "Domain: $DOMAIN"
print_success "Branch: $BRANCH"
print_success "Deployment time: ${elapsed_minutes}m ${elapsed_seconds}s"
echo ""
echo "Visit your site at: https://$DOMAIN"
echo ""
print_warning "If you encounter any issues, you can perform a rollback using:"
echo "  $0 $ENVIRONMENT $current_branch rollback"
echo ""

exit 0

