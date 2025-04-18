#!/bin/bash
#
# Application Health Check Script
# This script performs health checks on various components of the application
# and reports any issues it finds.
#
# Usage: ./health-check.sh [url] [timeout] [app_path]
# Example: ./health-check.sh https://your-domain.com 5 /home/forge/your-domain.com
#

# Define variables
APP_URL="${1:-https://your-domain.com}"
TIMEOUT="${2:-5}"
APP_PATH="${3:-/home/forge/your-domain.com}"
RESULTS_FILE="health_check_results.log"
TEMP_DIR=$(mktemp -d)
EXIT_CODE=0

# Set colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Function to print colored status messages
print_status() {
    local status=$1
    local message=$2
    
    case $status in
        "OK")
            echo -e "${GREEN}[OK]${NC} $message"
            ;;
        "WARNING")
            echo -e "${YELLOW}[WARNING]${NC} $message"
            ;;
        "ERROR")
            echo -e "${RED}[ERROR]${NC} $message"
            EXIT_CODE=1
            ;;
        *)
            echo "$message"
            ;;
    esac
    
    # Log to results file
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] [$status] $message" >> "$RESULTS_FILE"
}

# Function to cleanup temporary files
cleanup() {
    rm -rf "$TEMP_DIR"
    echo "Health check completed with exit code: $EXIT_CODE"
    echo "Results saved to $RESULTS_FILE"
    exit $EXIT_CODE
}

# Set up trap for cleanup on script exit
trap cleanup EXIT

# Create results file
echo "=== Application Health Check - $(date '+%Y-%m-%d %H:%M:%S') ===" > "$RESULTS_FILE"
echo "Application URL: $APP_URL" >> "$RESULTS_FILE"
echo "Application Path: $APP_PATH" >> "$RESULTS_FILE"
echo "===========================================================" >> "$RESULTS_FILE"

echo "=== Starting health checks ==="

# 1. Check Application HTTP Status
echo "1. Checking application HTTP status..."
HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" -m "$TIMEOUT" "$APP_URL")

if [ "$HTTP_STATUS" = "200" ]; then
    print_status "OK" "Application is responding with HTTP 200 OK"
elif [ "$HTTP_STATUS" = "302" ] || [ "$HTTP_STATUS" = "301" ]; then
    print_status "OK" "Application is responding with a redirect ($HTTP_STATUS)"
else
    print_status "ERROR" "Application returned unexpected status code: $HTTP_STATUS"
fi

# Create a dedicated health check endpoint if we can access the app directory
if [ -d "$APP_PATH" ]; then
    # 2. Check Database Connection
    echo "2. Checking database connection..."
    
    if cd "$APP_PATH" && php artisan migrate:status > /dev/null 2>&1; then
        print_status "OK" "Database connection is working"
    else
        print_status "ERROR" "Database connection failed"
    fi
    
    # 3. Check Redis Connection
    echo "3. Checking Redis connection..."
    
    REDIS_CHECK=$(cd "$APP_PATH" && php artisan tinker --execute="echo Redis::connection()->ping();" 2>/dev/null | grep -i pong)
    if [ -n "$REDIS_CHECK" ]; then
        print_status "OK" "Redis connection is working"
    else
        print_status "WARNING" "Redis connection test failed"
    fi
    
    # 4. Check Queue Processing
    echo "4. Checking queue processing..."
    
    # Check if queue processes are running
    if ps aux | grep -v grep | grep -q "queue:work"; then
        print_status "OK" "Queue workers are running"
    else
        print_status "WARNING" "No queue workers seem to be running"
    fi
    
    # Try to dispatch a test job to queue
    QUEUE_CHECK=$(cd "$APP_PATH" && php artisan tinker --execute="Bus::dispatch(new \Illuminate\Foundation\Bus\PendingDispatch(new class {
        public function handle() { return true; }
    })); echo 'Job dispatched successfully';" 2>/dev/null | grep -i "dispatched successfully")
    
    if [ -n "$QUEUE_CHECK" ]; then
        print_status "OK" "Test job dispatched to queue successfully"
    else
        print_status "WARNING" "Could not dispatch test job to queue"
    fi
    
    # 5. Check Storage Permissions
    echo "5. Checking storage permissions..."
    
    STORAGE_PATH="$APP_PATH/storage"
    if [ -d "$STORAGE_PATH" ]; then
        # Try to write a test file
        TEST_FILE="$STORAGE_PATH/health_check_test_$(date +%s).txt"
        if echo "test" > "$TEST_FILE" 2>/dev/null; then
            print_status "OK" "Storage directory is writable"
            rm -f "$TEST_FILE"
        else
            print_status "ERROR" "Storage directory is not writable"
        fi
        
        # Check if other important directories are writable
        for dir in framework/sessions framework/cache framework/views logs; do
            if [ -d "$STORAGE_PATH/$dir" ] && [ -w "$STORAGE_PATH/$dir" ]; then
                print_status "OK" "Directory $dir is writable"
            else
                print_status "ERROR" "Directory $dir is not writable or does not exist"
            fi
        done
    else
        print_status "ERROR" "Storage directory not found"
    fi
else
    print_status "WARNING" "Cannot check database, Redis, queue, and storage - application path not accessible"
fi

# 6. Check SSL Certificate Validity
echo "6. Checking SSL certificate..."

# Extract domain from URL
DOMAIN=$(echo "$APP_URL" | sed -e 's|^[^/]*//||' -e 's|/.*$||')

if [[ "$APP_URL" == https://* ]]; then
    # Get certificate info and expiration date
    SSL_INFO=$(echo | openssl s_client -servername "$DOMAIN" -connect "$DOMAIN":443 2>/dev/null | openssl x509 -noout -enddate 2>/dev/null)
    
    if [ -n "$SSL_INFO" ]; then
        # Extract expiration date and convert to timestamp
        CERT_END_DATE=$(echo "$SSL_INFO" | grep 'notAfter=' | cut -d= -f2)
        CERT_END_TIMESTAMP=$(date -j -f "%b %d %H:%M:%S %Y %Z" "$CERT_END_DATE" +%s 2>/dev/null)
        CURRENT_TIMESTAMP=$(date +%s)
        
        # Calculate days until expiration
        SECONDS_REMAINING=$((CERT_END_TIMESTAMP - CURRENT_TIMESTAMP))
        DAYS_REMAINING=$((SECONDS_REMAINING / 86400))
        
        if [ $DAYS_REMAINING -lt 0 ]; then
            print_status "ERROR" "SSL certificate has expired"
        elif [ $DAYS_REMAINING -lt 30 ]; then
            print_status "WARNING" "SSL certificate will expire in $DAYS_REMAINING days"
        else
            print_status "OK" "SSL certificate is valid for $DAYS_REMAINING more days"
        fi
    else
        print_status "ERROR" "Could not retrieve SSL certificate information"
    fi
else
    print_status "WARNING" "Not using HTTPS - SSL certificate not checked"
fi

# Check application response time
echo "7. Checking application response time..."

RESPONSE_TIME=$(curl -s -w "%{time_total}\n" -o /dev/null -m "$TIMEOUT" "$APP_URL")
if (( $(echo "$RESPONSE_TIME < 1.0" | bc -l) )); then
    print_status "OK" "Response time is good: ${RESPONSE_TIME}s"
elif (( $(echo "$RESPONSE_TIME < 3.0" | bc -l) )); then
    print_status "WARNING" "Response time is slow: ${RESPONSE_TIME}s"
else
    print_status "ERROR" "Response time is very slow: ${RESPONSE_TIME}s"
fi

# Check disk space
echo "8. Checking disk space..."

DISK_USAGE=$(df -h / | awk 'NR==2 {print $5}' | sed 's/%//')
if [ "$DISK_USAGE" -lt 80 ]; then
    print_status "OK" "Disk usage is at ${DISK_USAGE}%"
elif [ "$DISK_USAGE" -lt 90 ]; then
    print_status "WARNING" "Disk usage is high: ${DISK_USAGE}%"
else
    print_status "ERROR" "Disk usage is critical: ${DISK_USAGE}%"
fi

# Summary
echo "=== Health check summary ==="
ERROR_COUNT=$(grep -c "\[ERROR\]" "$RESULTS_FILE")
WARNING_COUNT=$(grep -c "\[WARNING\]" "$RESULTS_FILE")
OK_COUNT=$(grep -c "\[OK\]" "$RESULTS_FILE")

echo "✅ Passed: $OK_COUNT checks"
echo "⚠️ Warnings: $WARNING_COUNT"
echo "❌ Errors: $ERROR_COUNT"

if [ $ERROR_COUNT -gt 0 ]; then
    echo -e "${RED}Some critical checks failed. Please review the issues.${NC}"
elif [ $WARNING_COUNT -gt 0 ]; then
    echo -e "${YELLOW}Some non-critical issues were found. Consider addressing them.${NC}"
else
    echo -e "${GREEN}All checks passed successfully!${NC}"
fi

# Exit code will be set by the cleanup function

