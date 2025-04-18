#!/bin/bash
#
# Environment Configuration Generator
# This script generates proper .env files for different environments
# based on a template and environment-specific overrides.
#
# Usage: ./generate-env.sh [environment] [output_file]
# Example: ./generate-env.sh production .env.production
#

# Define variables
ENV="${1:-production}"
OUTPUT_FILE="${2:-.env.$ENV}"
BASE_TEMPLATE=".env.example"
ENV_OVERRIDE_FILE=".env.$ENV.override"

# Check if base template exists
if [[ ! -f "$BASE_TEMPLATE" ]]; then
    echo "Error: Base template file '$BASE_TEMPLATE' not found."
    exit 1
fi

echo "Generating environment configuration for $ENV environment..."

# Create temporary file for processing
TMP_FILE=$(mktemp)

# Copy base template to temporary file
cp "$BASE_TEMPLATE" "$TMP_FILE"

# Process environment-specific values
case "$ENV" in
    local)
        # Local development settings
        sed -i '' 's/APP_ENV=.*/APP_ENV=local/g' "$TMP_FILE"
        sed -i '' 's/APP_DEBUG=.*/APP_DEBUG=true/g' "$TMP_FILE"
        sed -i '' 's/APP_URL=.*/APP_URL=http:\/\/localhost:8000/g' "$TMP_FILE"
        
        # Local database settings
        sed -i '' 's/DB_CONNECTION=.*/DB_CONNECTION=mysql/g' "$TMP_FILE"
        sed -i '' 's/DB_HOST=.*/DB_HOST=127.0.0.1/g' "$TMP_FILE"
        sed -i '' 's/DB_PORT=.*/DB_PORT=3306/g' "$TMP_FILE"
        sed -i '' 's/DB_DATABASE=.*/DB_DATABASE=laravel_saas_local/g' "$TMP_FILE"
        sed -i '' 's/DB_USERNAME=.*/DB_USERNAME=root/g' "$TMP_FILE"
        sed -i '' 's/DB_PASSWORD=.*/DB_PASSWORD=secret/g' "$TMP_FILE"
        
        # Cache and queue settings for local
        sed -i '' 's/CACHE_DRIVER=.*/CACHE_DRIVER=file/g' "$TMP_FILE"
        sed -i '' 's/QUEUE_CONNECTION=.*/QUEUE_CONNECTION=sync/g' "$TMP_FILE"
        sed -i '' 's/SESSION_DRIVER=.*/SESSION_DRIVER=file/g' "$TMP_FILE"
        ;;

    testing)
        # Testing environment settings
        sed -i '' 's/APP_ENV=.*/APP_ENV=testing/g' "$TMP_FILE"
        sed -i '' 's/APP_DEBUG=.*/APP_DEBUG=true/g' "$TMP_FILE"
        sed -i '' 's/APP_URL=.*/APP_URL=http:\/\/localhost:8000/g' "$TMP_FILE"
        
        # Testing database settings (using in-memory SQLite)
        sed -i '' 's/DB_CONNECTION=.*/DB_CONNECTION=sqlite/g' "$TMP_FILE"
        sed -i '' 's/DB_DATABASE=.*/DB_DATABASE=:memory:/g' "$TMP_FILE"
        
        # Cache and queue settings for testing
        sed -i '' 's/CACHE_DRIVER=.*/CACHE_DRIVER=array/g' "$TMP_FILE"
        sed -i '' 's/QUEUE_CONNECTION=.*/QUEUE_CONNECTION=sync/g' "$TMP_FILE"
        sed -i '' 's/SESSION_DRIVER=.*/SESSION_DRIVER=array/g' "$TMP_FILE"
        sed -i '' 's/MAIL_MAILER=.*/MAIL_MAILER=array/g' "$TMP_FILE"
        ;;

    staging)
        # Staging environment settings
        sed -i '' 's/APP_ENV=.*/APP_ENV=staging/g' "$TMP_FILE"
        sed -i '' 's/APP_DEBUG=.*/APP_DEBUG=false/g' "$TMP_FILE"
        sed -i '' 's/APP_URL=.*/APP_URL=https:\/\/staging.your-domain.com/g' "$TMP_FILE"
        
        # Staging database settings (these should be overridden with secure values)
        sed -i '' 's/DB_CONNECTION=.*/DB_CONNECTION=mysql/g' "$TMP_FILE"
        sed -i '' 's/DB_HOST=.*/DB_HOST=127.0.0.1/g' "$TMP_FILE"
        sed -i '' 's/DB_PORT=.*/DB_PORT=3306/g' "$TMP_FILE"
        sed -i '' 's/DB_DATABASE=.*/DB_DATABASE=laravel_saas_staging/g' "$TMP_FILE"
        sed -i '' 's/DB_USERNAME=.*/DB_USERNAME=forge/g' "$TMP_FILE"
        sed -i '' 's/DB_PASSWORD=.*/DB_PASSWORD=__STAGING_DB_PASSWORD__/g' "$TMP_FILE"
        
        # Cache and queue settings for staging
        sed -i '' 's/CACHE_DRIVER=.*/CACHE_DRIVER=redis/g' "$TMP_FILE"
        sed -i '' 's/QUEUE_CONNECTION=.*/QUEUE_CONNECTION=redis/g' "$TMP_FILE"
        sed -i '' 's/SESSION_DRIVER=.*/SESSION_DRIVER=redis/g' "$TMP_FILE"
        ;;

    production)
        # Production environment settings
        sed -i '' 's/APP_ENV=.*/APP_ENV=production/g' "$TMP_FILE"
        sed -i '' 's/APP_DEBUG=.*/APP_DEBUG=false/g' "$TMP_FILE"
        sed -i '' 's/APP_URL=.*/APP_URL=https:\/\/your-domain.com/g' "$TMP_FILE"
        
        # Production database settings (these should be overridden with secure values)
        sed -i '' 's/DB_CONNECTION=.*/DB_CONNECTION=mysql/g' "$TMP_FILE"
        sed -i '' 's/DB_HOST=.*/DB_HOST=127.0.0.1/g' "$TMP_FILE"
        sed -i '' 's/DB_PORT=.*/DB_PORT=3306/g' "$TMP_FILE"
        sed -i '' 's/DB_DATABASE=.*/DB_DATABASE=laravel_saas_production/g' "$TMP_FILE"
        sed -i '' 's/DB_USERNAME=.*/DB_USERNAME=forge/g' "$TMP_FILE"
        sed -i '' 's/DB_PASSWORD=.*/DB_PASSWORD=__PRODUCTION_DB_PASSWORD__/g' "$TMP_FILE"
        
        # Cache and queue settings for production
        sed -i '' 's/CACHE_DRIVER=.*/CACHE_DRIVER=redis/g' "$TMP_FILE"
        sed -i '' 's/QUEUE_CONNECTION=.*/QUEUE_CONNECTION=redis/g' "$TMP_FILE"
        sed -i '' 's/SESSION_DRIVER=.*/SESSION_DRIVER=redis/g' "$TMP_FILE"
        ;;

    *)
        echo "Warning: Unknown environment '$ENV'. Using default settings."
        ;;
esac

# Apply any override values if the override file exists
if [[ -f "$ENV_OVERRIDE_FILE" ]]; then
    echo "Applying environment-specific overrides from $ENV_OVERRIDE_FILE..."
    
    # For each line in the override file, apply it to the temporary file
    while IFS= read -r line; do
        # Skip comments and empty lines
        [[ "$line" =~ ^# || -z "$line" ]] && continue
        
        # Extract key from the line
        key=$(echo "$line" | cut -d= -f1)
        
        # Skip if key is empty
        [[ -z "$key" ]] && continue
        
        # Remove the existing line with this key and append the new line
        sed -i '' "/^$key=/d" "$TMP_FILE"
        echo "$line" >> "$TMP_FILE"
    done < "$ENV_OVERRIDE_FILE"
fi

# Move temporary file to output file
mv "$TMP_FILE" "$OUTPUT_FILE"

echo "Environment configuration for $ENV has been generated at $OUTPUT_FILE"
echo ""
echo "Note: For production use, make sure to set real secure values for passwords and secrets."
echo "      Consider using a secure method to store and apply these values."

exit 0

