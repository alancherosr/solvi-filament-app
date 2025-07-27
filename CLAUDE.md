## Database Safety Guideline

**Do not use `php artisan migrate:refresh`, `php artisan migrate:fresh`, or similar commands that drop or recreate all tables in production or shared environments.**

These commands will delete all data from the database. Use them only in local development when you are certain that no important data will be lost. Always back up your database before running destructive migration commands.

# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a Laravel-based personal finances application built with:

- **Laravel 12.0** (PHP 8.2+) as the backend framework
- **Filament 3.2** for the admin panel interface
- **Vite** with **TailwindCSS 4.0** for frontend asset building
- **PostgreSQL** as the database (testing uses `laravel_testing` database)

The application uses Filament's admin panel at `/admin` with Amber as the primary color theme.

## Development Commands

### Starting Development Environment

```bash
# Start all services (server, queue, logs, vite) concurrently
composer run dev

# Alternative: Start individual services
php artisan serve              # Development server
php artisan queue:listen --tries=1  # Queue worker
php artisan pail --timeout=0  # Log viewer
npm run dev                    # Vite development server
```

### Building and Assets

```bash
npm run build                  # Build production assets
npm run dev                    # Start Vite development server
```

### Testing

```bash
composer run test              # Clear config and run PHPUnit tests
php artisan test               # Run tests directly
vendor/bin/phpunit             # Run PHPUnit directly
```

### Code Quality

```bash
vendor/bin/pint                # Laravel Pint (PHP CS Fixer)
```

### Database Operations

```bash
php artisan migrate            # Run migrations
php artisan migrate:fresh --seed  # Fresh migration with seeding
php artisan db:seed            # Run database seeders
```

### Filament Operations

```bash
php artisan filament:upgrade   # Upgrade Filament components
php artisan make:filament-resource ModelName  # Create new Filament resource
php artisan make:filament-page PageName       # Create new Filament page
php artisan make:filament-widget WidgetName   # Create new Filament widget
```

### IDE Helpers

```bash
php artisan ide-helper:generate    # Generate IDE helper files
php artisan ide-helper:models      # Generate model docblocks
php artisan ide-helper:meta        # Generate meta file
```

## Architecture

### Directory Structure

- `app/Filament/` - Filament admin panel components (Resources, Pages, Widgets)
- `app/Http/Controllers/` - HTTP controllers
- `app/Models/` - Eloquent models
- `app/Providers/Filament/AdminPanelProvider.php` - Filament admin panel configuration
- `resources/views/` - Blade templates
- `resources/css/app.css` - Application CSS
- `resources/js/app.js` - Application JavaScript
- `database/migrations/` - Database migrations
- `database/seeders/` - Database seeders
- `tests/Feature/` - Feature tests
- `tests/Unit/` - Unit tests

### Key Components

- **Filament Admin Panel**: Configured in `AdminPanelProvider.php` with automatic resource/page/widget discovery
- **User Authentication**: Built-in Laravel authentication with Filament login
- **Database**: PostgreSQL with standard Laravel migrations (users, cache, jobs tables)
- **Frontend**: Vite + TailwindCSS 4.0 with hot reloading
- **Queue System**: Laravel queues configured for background job processing

### Configuration Files

- `composer.json` - PHP dependencies and custom scripts
- `package.json` - Node.js dependencies and build scripts
- `vite.config.js` - Vite configuration with Laravel plugin and TailwindCSS
- `phpunit.xml` - PHPUnit test configuration with PostgreSQL testing database

## Development Workflow

1. Use `composer run dev` to start the full development environment
2. Access the admin panel at `http://localhost:8000/admin`
3. Create Filament resources for new models using `php artisan make:filament-resource`
4. Run tests with `composer run test` before committing changes
5. Use Laravel Pint (`vendor/bin/pint`) for code formatting

## Important Notes

- The application uses PostgreSQL in both development and testing environments
- Filament resources, pages, and widgets are auto-discovered from their respective directories
- The admin panel uses Amber as the primary color theme
- IDE helpers are available via `barryvdh/laravel-ide-helper` package
- Queue processing and log monitoring are included in the development stack
