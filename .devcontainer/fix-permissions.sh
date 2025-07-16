#!/bin/bash

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${YELLOW}🔐 Fixing permissions for Laravel development...${NC}"

# Fix ownership for the entire workspace
sudo chown -R vscode:vscode /workspace

# Set proper permissions for Laravel directories
chmod -R 755 /workspace
chmod -R 775 storage bootstrap/cache 2>/dev/null || true
chown -R vscode:www-data storage bootstrap/cache public 2>/dev/null || true

# Fix vendor directory permissions if it exists
if [ -d "vendor" ]; then
    chmod -R 755 vendor
    chown -R vscode:vscode vendor
fi

# Fix node_modules permissions if it exists
if [ -d "node_modules" ]; then
    chmod -R 755 node_modules
    chown -R vscode:vscode node_modules
fi

# Fix composer cache permissions
sudo chown -R vscode:vscode ~/.composer 2>/dev/null || true

echo -e "${GREEN}✅ Permissions fixed successfully!${NC}"
