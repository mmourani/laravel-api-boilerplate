#!/bin/bash
#
# Laravel Forge Configuration Script
# This script helps configure Laravel Forge for multiple environments
# (development, staging, production) and securely stores credentials.
#
# Usage: ./configure-forge.sh
#

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

# Create configuration directories if they don't exist
mkdir -p "$CONFIG_DIR"
mkdir -p "$ENV_DIR"

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
}

# Function to securely encrypt data
encrypt_data() {
    local data="$1"
    local output_file="$2"
    
    # Generate a random passphrase
    if [ ! -f "$CONFIG_DIR/passphrase" ]; then
        openssl rand -base64 48 > "$CONFIG_DIR/passphrase"
        chmod 600 "$CONFIG_DIR/passphrase"
    fi
    
    # Encrypt the data
    echo "$data" | openssl enc -aes-256-cbc -salt -pbkdf2 -out "$output_file" -pass file:"$CONFIG_DIR/passphrase"
    chmod 600 "$output_file"
}

# Function to decrypt data
decrypt_data() {
    local input_file="$1"
    
    if [ ! -f "$CONFIG_DIR/passphrase" ]; then
        print_error "Passphrase file not found. Cannot decrypt data."
        return 1
    fi
    
    openssl enc -aes-256-cbc -d -salt -pbkdf2 -in "$input_file" -pass file:"$CONFIG_DIR/passphrase"
}

# Function to validate Forge API token
validate_forge_token() {
    local token="$1"
    
    # Make a request to the Forge API to check if the token is valid
    local response=$(curl -s -H "Authorization: Bearer $token" -H "Accept: application/json" \
                         https://forge.laravel.com/api/v1/servers)
    
    # Check if the response is valid JSON and doesn't contain an error message
    if echo "$response" | jq -e . >/dev/null 2>&1 && ! echo "$response" | jq -e '.message' >/dev/null 2>&1; then
        return 0 # Token is valid
    else
        return 1 # Token is invalid
    fi
}

# Function to configure an environment
configure_environment() {
    local env_name="$1"
    local env_file="$ENV_DIR/$env_name.json"
    
    print_header "Configuring $env_name environment"
    
    # Get server ID
    read -p "Enter Forge server ID for $env_name: " server_id
    
    # Get domain name
    case "$env_name" in
        development)
            read -p "Enter domain name for development (e.g., dev.example.com): " domain
            ;;
        staging)
            read -p "Enter domain name for staging (e.g., staging.example.com): " domain
            ;;
        production)
            read -p "Enter domain name for production (e.g., example.com): " domain
            ;;
    esac
    
    # Get database configuration
    read -p "Enter database name for $env_name: " db_name
    read -p "Enter database user for $env_name: " db_user
    read -s -p "Enter database password for $env_name: " db_password
    echo
    
    # Get additional environment-specific settings
    case "$env_name" in
        development)
            read -p "Enable debug mode? [Y/n]: " debug_mode
            debug_mode=${debug_mode:-Y}
            
            if [[ "$debug_mode" =~ ^[Yy]$ ]]; then
                app_debug="true"
            else
                app_debug="false"
            fi
            
            # Default queue driver for development
            queue_driver="sync"
            ;;
        staging)
            app_debug="false"
            queue_driver="redis"
            
            read -p "Number of queue workers for staging [1]: " queue_workers
            queue_workers=${queue_workers:-1}
            ;;
        production)
            app_debug="false"
            queue_driver="redis"
            
            read -p "Number of queue workers for production [2]: " queue_workers
            queue_workers=${queue_workers:-2}
            ;;
    esac
    
    # Create environment configuration JSON
    cat > "$env_file" << EOF
{
    "environment": "$env_name",
    "server_id": "$server_id",
    "domain": "$domain",
    "database": {
        "name": "$db_name",
        "user": "$db_user",
        "password": "$db_password"
    },
    "app_settings": {
        "debug": $app_debug,
        "queue_driver": "$queue_driver"
    }
EOF
    
    # Add environment-specific settings
    case "$env_name" in
        staging|production)
            cat >> "$env_file" << EOF
,
    "queue_workers": $queue_workers
EOF
            ;;
    esac
    
    # Close the JSON object
    echo "}" >> "$env_file"
    
    # Secure the environment file
    chmod 600 "$env_file"
    
    print_success "$env_name environment configured successfully"
}

# Function to generate environment files
generate_env_files() {
    print_header "Generating .env files for all environments"
    
    # Iterate through all environment configurations
    for env_file in "$ENV_DIR"/*.json; do
        if [ -f "$env_file" ]; then
            env_name=$(basename "$env_file" .json)
            env_output=".env.$env_name"
            
            print_warning "Generating $env_output"
            
            # Read environment configuration
            env_config=$(cat "$env_file")
            
            # Extract values from configuration
            domain=$(echo "$env_config" | jq -r '.domain')
            db_name=$(echo "$env_config" | jq -r '.database.name')
            db_user=$(echo "$env_config" | jq -r '.database.user')
            db_password=$(echo "$env_config" | jq -r '.database.password')
            app_debug=$(echo "$env_config" | jq -r '.app_settings.debug')
            queue_driver=$(echo "$env_config" | jq -r '.app_settings.queue_driver')
            
            # Generate .env file based on template
            cp .env.example "$env_output"
            
            # Replace values in the .env file
            sed -i.bak "s#APP_ENV=.*#APP_ENV=$env_name#g" "$env_output"
            sed -i.bak "s#APP_DEBUG=.*#APP_DEBUG=$app_debug#g" "$env_output"
            sed -i.bak "s#APP_URL=.*#APP_URL=https://$domain#g" "$env_output"
            
            sed -i.bak "s#DB_DATABASE=.*#DB_DATABASE=$db_name#g" "$env_output"
            sed -i.bak "s#DB_USERNAME=.*#DB_USERNAME=$db_user#g" "$env_output"
            sed -i.bak "s#DB_PASSWORD=.*#DB_PASSWORD=$db_password#g" "$env_output"
            
            sed -i.bak "s#QUEUE_CONNECTION=.*#QUEUE_CONNECTION=$queue_driver#g" "$env_output"
            
            # Clean up backup files
            rm -f "$env_output.bak"
            
            print_success "Generated $env_output"
        fi
    done
}

# Main script starts here
print_header "Laravel Forge Configuration"
echo "This script will help you configure Laravel Forge for your project."
echo "You'll need your Forge API token and server information ready."
echo "Three environments will be configured: development, staging, and production."
echo

# Check if jq is installed
if ! command -v jq &> /dev/null; then
    print_error "The 'jq' command is required but not installed."
    echo "Please install it and try again."
    echo "  - On macOS: brew install jq"
    echo "  - On Ubuntu/Debian: sudo apt-get install jq"
    echo "  - On CentOS/RHEL: sudo yum install jq"
    exit 1
fi

# Check if OpenSSL is installed
if ! command -v openssl &> /dev/null; then
    print_error "OpenSSL is required but not installed."
    echo "Please install it and try again."
    exit 1
fi

# Check if credentials file already exists
if [ -f "$CREDENTIALS_FILE" ]; then
    read -p "Credentials file already exists. Do you want to reconfigure? [y/N]: " reconfigure
    reconfigure=${reconfigure:-N}
    
    if [[ ! "$reconfigure" =~ ^[Yy]$ ]]; then
        print_warning "Skipping credentials configuration"
    else
        # Prompt for Forge API token
        read -s -p "Enter your Laravel Forge API token: " forge_token
        echo
        
        print_warning "Validating Forge API token..."
        if validate_forge_token "$forge_token"; then
            print_success "Forge API token is valid"
            
            # Encrypt and store the token
            encrypt_data "$forge_token" "$CREDENTIALS_FILE"
            print_success "Credentials stored securely"
        else
            print_error "Invalid Forge API token. Please check and try again."
            exit 1
        fi
    fi
else
    # Prompt for Forge API token
    read -s -p "Enter your Laravel Forge API token: " forge_token
    echo
    
    print_warning "Validating Forge API token..."
    if validate_forge_token "$forge_token"; then
        print_success "Forge API token is valid"
        
        # Encrypt and store the token
        encrypt_data "$forge_token" "$CREDENTIALS_FILE"
        print_success "Credentials stored securely"
    else
        print_error "Invalid Forge API token. Please check and try again."
        exit 1
    fi
fi

# Configure all environments
for env in development staging production; do
    env_file="$ENV_DIR/$env.json"
    
    if [ -f "$env_file" ]; then
        read -p "$env environment configuration already exists. Reconfigure? [y/N]: " reconfigure
        reconfigure=${reconfigure:-N}
        
        if [[ "$reconfigure" =~ ^[Yy]$ ]]; then
            configure_environment "$env"
        else
            print_warning "Skipping $env environment configuration"
        fi
    else
        configure_environment "$env"
    fi
done

# Generate environment files
read -p "Generate .env files for all environments? [Y/n]: " generate_envs
generate_envs=${generate_envs:-Y}

if [[ "$generate_envs" =~ ^[Yy]$ ]]; then
    generate_env_files
fi

print_header "Configuration Complete"
echo "Your Laravel Forge configuration is now complete."
echo
echo "Configuration files are stored in: ${BOLD}$CONFIG_DIR${NORMAL}"
echo "Environment files have been created for each environment."
echo
echo "To deploy to an environment, run:"
echo "  bin/deploy-forge.sh [environment]"
echo
echo "Example:"
echo "  bin/deploy-forge.sh staging"

exit 0

