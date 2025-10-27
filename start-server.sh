#!/bin/bash

# Laravel Development Server Configuration
# This script starts Laravel with optimized settings for handling large responses

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}Starting Laravel Development Server with Optimizations...${NC}"

# Clear caches first
echo -e "${YELLOW}Clearing caches...${NC}"
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Optimize for development
echo -e "${YELLOW}Optimizing for development...${NC}"
php artisan config:cache

# Set environment variables for this session
export PHP_CLI_SERVER_WORKERS=4
export MEMORY_LIMIT=512M

# Start server with optimized PHP settings
echo -e "${GREEN}Starting server on http://0.0.0.0:8000${NC}"
echo -e "${GREEN}Access URLs:${NC}"
echo -e "${YELLOW}- Local: http://localhost:8000${NC}"
echo -e "${YELLOW}- Network: http://[IP_ANDA]:8000${NC}"
echo -e "${YELLOW}Press Ctrl+C to stop the server${NC}"

php -d memory_limit=512M \
    -d max_execution_time=60 \
    -d output_buffering=8192 \
    -d zlib.output_compression=On \
    -d zlib.output_compression_level=6 \
    artisan serve --host=0.0.0.0 --port=8000