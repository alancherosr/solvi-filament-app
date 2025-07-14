#!/bin/bash

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}🚀 Setting up Personal Finances Laravel + Filament development environment...${NC}"

# Wait for PostgreSQL to be ready
echo -e "${YELLOW}⏳ Waiting for PostgreSQL to be ready...${NC}"
while ! pg_isready -h postgres -p 5432 -U postgres; do
    sleep 1
done
echo -e "${GREEN}✅ PostgreSQL is ready!${NC}"

# Install PHP dependencies
if [ ! -d "vendor" ]; then
    echo -e "${YELLOW}📦 Installing PHP dependencies...${NC}"
    composer install --no-interaction --prefer-dist --optimize-autoloader
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
chmod -R 775 storage bootstrap/cache
chown -R vscode:www-data storage bootstrap/cache public

# Clear and cache config
echo -e "${YELLOW}🧹 Clearing and caching configuration...${NC}"
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan config:cache

# Start Apache
echo -e "${YELLOW}🚀 Starting Apache web server...${NC}"
sudo service apache2 start

echo -e "${GREEN}🎉 Setup complete! Your Personal Finances app is ready to go!${NC}"
echo -e "${YELLOW}📝 To access the admin panel, visit: http://localhost:8000/admin${NC}"
echo -e "${YELLOW}📚 Check the database seeder output for admin credentials${NC}"
echo -e "${YELLOW}🔧 Apache is serving the application on port 8000${NC}"
echo -e "${YELLOW}🐘 Running PHP $(php -v | head -n1 | cut -d' ' -f2) with PostgreSQL 17${NC}"
