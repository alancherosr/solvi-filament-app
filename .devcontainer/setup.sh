#!/bin/bash

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${GREEN}🚀 Setting up Personal Finances Laravel + Filament development environment...${NC}"

# Source bashrc to get Claude Code in PATH
source ~/.bashrc

# Verify Claude Code installation
if command -v claude &> /dev/null; then
    echo -e "${GREEN}✅ Claude Code is available!${NC}"
    echo -e "${BLUE}💡 Run 'claude auth' to authenticate when ready${NC}"
else
    echo -e "${YELLOW}⚠️ Claude Code not found in PATH, installing...${NC}"
    curl -fsSL https://claude.ai/cli/install.sh | sh
    source ~/.bashrc
fi

# Fix initial permissions
echo -e "${YELLOW}🔐 Fixing initial permissions...${NC}"
sudo chown -R vscode:vscode /workspace
sudo chmod -R 755 /workspace

# Wait for PostgreSQL to be ready
echo -e "${YELLOW}⏳ Waiting for PostgreSQL to be ready...${NC}"
while ! pg_isready -h postgres -p 5432 -U postgres; do
    sleep 1
done
echo -e "${GREEN}✅ PostgreSQL is ready!${NC}"

# Install PHP dependencies
if [ ! -d "vendor" ] || [ ! -f "vendor/autoload.php" ]; then
    echo -e "${YELLOW}📦 Installing PHP dependencies...${NC}"
    # Ensure composer cache directory has correct permissions
    sudo chown -R vscode:vscode ~/.composer 2>/dev/null || true
    composer install --no-interaction --prefer-dist --optimize-autoloader
    # Fix vendor directory permissions after install
    sudo chown -R vscode:vscode vendor
    chmod -R 755 vendor
else
    echo -e "${GREEN}✅ PHP dependencies already installed${NC}"
fi

# Install Node.js dependencies
if [ ! -d "node_modules" ]; then
    echo -e "${YELLOW}📦 Installing Node.js dependencies...${NC}"
    npm install
else
    echo -e "${GREEN}✅ Node.js dependencies already installed${NC}"
fi

# Build assets
echo -e "${YELLOW}🏗️ Building frontend assets...${NC}"
npm run build

# Setup environment file
if [ ! -f ".env" ]; then
    echo -e "${YELLOW}⚙️ Setting up environment file...${NC}"
    cp .env.example .env
    
    # Update database configuration
    sed -i 's/DB_HOST=127.0.0.1/DB_HOST=postgres/' .env
    sed -i 's/DB_CONNECTION=mysql/DB_CONNECTION=pgsql/' .env
    sed -i 's/DB_PORT=3306/DB_PORT=5432/' .env
    sed -i 's/DB_DATABASE=laravel/DB_DATABASE=personal_finances/' .env
    sed -i 's/DB_USERNAME=root/DB_USERNAME=postgres/' .env
    sed -i 's/DB_PASSWORD=/DB_PASSWORD=password/' .env
    
    # Generate application key
    php artisan key:generate --no-interaction
else
    echo -e "${GREEN}✅ Environment file already exists${NC}"
fi

# Run migrations and seed database
echo -e "${YELLOW}🗄️ Setting up database...${NC}"
php artisan migrate --seed --no-interaction

# Set proper permissions
echo -e "${YELLOW}🔐 Setting proper permissions...${NC}"
# Set ownership for vscode user on all project files
sudo chown -R vscode:vscode /workspace
# Set proper permissions for Laravel directories
chmod -R 775 storage bootstrap/cache
chown -R vscode:www-data storage bootstrap/cache public
# Ensure vendor directory has correct permissions
if [ -d "vendor" ]; then
    chmod -R 755 vendor
    chown -R vscode:vscode vendor
fi

# Clear and cache config
echo -e "${YELLOW}🧹 Clearing and caching configuration...${NC}"
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan config:cache

# Create Claude Code instructions file
echo -e "${YELLOW}📝 Creating Claude Code instructions...${NC}"
cat > claude-instructions.md << 'EOF'
# Personal Finance Management App - Claude Code Instructions

## Project Overview
Transform a Google Sheets-based budget tracker into a Laravel + Filament admin panel application for personal finance management.

## Database Schema

### 1. Accounts
- id, name, type (checking, savings, credit_card, cash, investment), balance, currency (default USD), is_active, description, created_at, updated_at

### 2. Categories  
- id, name, type (income, expense), parent_id (nullable for subcategories), color, icon, description, is_active, created_at, updated_at

### 3. Transactions
- id, account_id, category_id, amount (decimal), description, transaction_date, type (income, expense, transfer), reference_number, notes, is_reconciled, created_at, updated_at

### 4. Budgets
- id, category_id, amount (decimal), period (monthly, yearly), start_date, end_date, is_active, created_at, updated_at

### 5. Recurring Transactions
- id, account_id, category_id, amount, description, frequency (weekly, monthly, yearly), next_due_date, end_date (nullable), is_active, created_at, updated_at

## Implementation Requirements

### Phase 1: Core Structure
1. Create all migrations with proper foreign keys and indexes
2. Create Eloquent models with relationships and validation rules
3. Create Filament resources for all models with proper forms and tables
4. Add basic authentication using Filament's built-in auth

### Phase 2: Dashboard & Widgets
1. Account balance overview widget
2. Monthly spending by category chart
3. Recent transactions list
4. Budget vs actual spending widget
5. Income vs expenses this month

### Phase 3: Advanced Features
1. Transaction import from CSV/Excel
2. Automated transaction categorization
3. Recurring transaction processing (scheduled job)
4. Financial reports with charts
5. Budget alerts when approaching limits

### Phase 4: Polish
1. Mobile-responsive design
2. Export functionality (PDF reports)
3. Transaction search and filtering
4. Currency conversion support
5. Data backup and restore

## Technical Specifications
- Laravel 10+
- Filament 3.x
- PostgreSQL database
- Livewire components for interactivity
- Chart.js for visualizations
- Proper validation and authorization
- Comprehensive test coverage

## File Structure
- Models: app/Models/
- Filament Resources: app/Filament/Resources/
- Migrations: database/migrations/
- Seeders: database/seeders/
- Tests: tests/Feature/ and tests/Unit/

Start with Phase 1 and implement incrementally. Each model should have proper seeders for development data.
EOF

# Start Apache
echo -e "${YELLOW}🚀 Starting Apache web server...${NC}"
sudo service apache2 start

echo -e "${GREEN}🎉 Setup complete! Your Personal Finances app is ready to go!${NC}"
echo -e "${YELLOW}📝 To access the admin panel, visit: http://localhost:8000/admin${NC}"
echo -e "${YELLOW}📚 Check the database seeder output for admin credentials${NC}"
echo -e "${YELLOW}🔧 Apache is serving the application on port 8000${NC}"
echo -e "${YELLOW}🐘 Running PHP $(php -v | head -n1 | cut -d' ' -f2) with PostgreSQL 17${NC}"
echo -e "${BLUE}🤖 Claude Code is ready! Run these commands to get started:${NC}"
echo -e "${BLUE}   1. claude auth (authenticate first)${NC}"
echo -e "${BLUE}   2. claude init (initialize project)${NC}"
echo -e "${BLUE}   3. claude 'Implement the personal finance app according to claude-instructions.md'${NC}"