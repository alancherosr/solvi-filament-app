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
