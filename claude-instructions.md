# Personal Finance Management App - Claude Code Instructions

## Project Overview
Transform a Google Sheets-based budget tracker into a Laravel + Filament admin panel application for personal finance management.

## Database Schema

### 1. Accounts Table
```sql
- id (bigserial primary key)
- name (varchar(255) not null) - e.g., "Bancolombia Ahorros", "Nequi", "Daviplata"
- type (varchar(20) not null check (type in ('checking', 'savings', 'credit_card', 'cash', 'investment')))
- balance (numeric(15,2) not null default 0.00)
- currency (varchar(3) not null default 'COP')
- is_active (boolean not null default true)
- description (text)
- account_number (varchar(255)) -- encrypted at application level
- created_at (timestamp with time zone not null default now())
- updated_at (timestamp with time zone not null default now())
- INDEX idx_accounts_type (type)
- INDEX idx_accounts_is_active (is_active)
- INDEX idx_accounts_currency (currency)
```

### 2. Categories Table
```sql
- id (bigserial primary key)
- name (varchar(255) not null) - e.g., "Mercado", "Salario", "Entretenimiento"
- type (varchar(10) not null check (type in ('income', 'expense')))
- parent_id (bigint references categories(id) on delete set null)
- color (varchar(7)) -- hex color like #FF5733
- icon (varchar(100)) -- icon class name like 'heroicon-o-shopping-cart'
- description (text)
- is_active (boolean not null default true)
- created_at (timestamp with time zone not null default now())
- updated_at (timestamp with time zone not null default now())
- INDEX idx_categories_type (type)
- INDEX idx_categories_parent_id (parent_id)
- INDEX idx_categories_is_active (is_active)
- UNIQUE constraint unique_category_name_per_parent (name, parent_id, type)
```

### 3. Transactions Table
```sql
- id (bigserial primary key)
- account_id (bigint not null references accounts(id) on delete cascade)
- category_id (bigint not null references categories(id) on delete restrict)
- amount (numeric(15,2) not null)
- description (varchar(500) not null)
- transaction_date (date not null)
- type (varchar(10) not null check (type in ('income', 'expense', 'transfer')))
- reference_number (varchar(100))
- notes (text)
- is_reconciled (boolean not null default false)
- transfer_to_account_id (bigint references accounts(id) on delete set null) -- for transfers
- created_at (timestamp with time zone not null default now())
- updated_at (timestamp with time zone not null default now())
- INDEX idx_transactions_account_id (account_id)
- INDEX idx_transactions_category_id (category_id)
- INDEX idx_transactions_date (transaction_date)
- INDEX idx_transactions_type (type)
- INDEX idx_transactions_amount (amount)
- INDEX idx_transactions_reconciled (is_reconciled)
- INDEX idx_transactions_transfer_account (transfer_to_account_id)
- INDEX idx_transactions_date_account (transaction_date, account_id) -- compound index
```

### 4. Budgets Table
```sql
- id (bigserial primary key)
- category_id (bigint not null references categories(id) on delete cascade)
- amount (numeric(15,2) not null)
- period (varchar(10) not null check (period in ('monthly', 'quarterly', 'yearly')))
- start_date (date not null)
- end_date (date not null)
- is_active (boolean not null default true)
- alert_threshold (numeric(5,2) not null default 80.00) -- percentage for alerts
- created_at (timestamp with time zone not null default now())
- updated_at (timestamp with time zone not null default now())
- INDEX idx_budgets_category_id (category_id)
- INDEX idx_budgets_period (period)
- INDEX idx_budgets_dates (start_date, end_date)
- INDEX idx_budgets_active (is_active)
- UNIQUE constraint unique_category_period (category_id, period, start_date)
- CHECK constraint check_valid_dates (start_date < end_date)
- CHECK constraint check_positive_amount (amount > 0)
- CHECK constraint check_valid_threshold (alert_threshold >= 0 AND alert_threshold <= 100)
```

### 5. Recurring Transactions Table
```sql
- id (bigserial primary key)
- account_id (bigint not null references accounts(id) on delete cascade)
- category_id (bigint not null references categories(id) on delete restrict)
- amount (numeric(15,2) not null)
- description (varchar(500) not null)
- frequency (varchar(15) not null check (frequency in ('daily', 'weekly', 'monthly', 'quarterly', 'yearly')))
- next_due_date (date not null)
- end_date (date) -- nullable for indefinite recurring
- is_active (boolean not null default true)
- auto_process (boolean not null default false)
- last_processed_at (timestamp with time zone)
- created_at (timestamp with time zone not null default now())
- updated_at (timestamp with time zone not null default now())
- INDEX idx_recurring_account_id (account_id)
- INDEX idx_recurring_category_id (category_id)
- INDEX idx_recurring_due_date (next_due_date)
- INDEX idx_recurring_frequency (frequency)
- INDEX idx_recurring_active (is_active)
- INDEX idx_recurring_auto_process (auto_process)
- CHECK constraint check_valid_end_date (end_date IS NULL OR end_date >= next_due_date)
- CHECK constraint check_positive_amount (amount != 0)
```

### 6. Transaction Rules Table (for auto-categorization)
```sql
- id (bigserial primary key)
- name (varchar(255) not null)
- conditions (jsonb not null) -- PostgreSQL native JSON with indexing
- category_id (bigint not null references categories(id) on delete cascade)
- is_active (boolean not null default true)
- priority (integer not null default 0)
- match_count (integer not null default 0) -- track usage statistics
- created_at (timestamp with time zone not null default now())
- updated_at (timestamp with time zone not null default now())
- INDEX idx_rules_category_id (category_id)
- INDEX idx_rules_active (is_active)
- INDEX idx_rules_priority (priority DESC)
- INDEX idx_rules_conditions USING gin (conditions) -- GIN index for JSON queries
- UNIQUE constraint unique_rule_name (name)
- CHECK constraint check_valid_priority (priority >= 0)
```

## Implementation Requirements

### Phase 1: Core Database & Models
```bash
# Commands to run:
claude "Create all database migrations for the personal finance app: accounts, categories, transactions, budgets, recurring_transactions, and transaction_rules tables with proper foreign keys, indexes, and validation"

claude "Create Eloquent models for all tables with proper relationships, validation rules, accessors, and mutators. Include soft deletes where appropriate"

claude "Create database seeders with realistic sample data for development, including default categories for common Colombian income/expense types (Salario, Servicios públicos, Mercado, Transporte, etc.) and sample Colombian bank accounts"
```

### Phase 2: Filament Admin Resources
```bash
claude "Create Filament resources for all models with comprehensive forms, tables, and filters. Include proper field types, validation, and user-friendly layouts"

claude "Create a Filament admin user seeder and authentication system. Set up proper authorization policies for resource access"

claude "Add relationship management in Filament forms - account selection, category hierarchies, and transfer handling in transactions"
```

### Phase 3: Dashboard & Widgets
```bash
claude "Create a Filament dashboard with these widgets:
1. Account balances overview (cards showing each account balance)
2. Monthly spending by category (pie chart)
3. Recent transactions table (last 10 transactions)
4. Budget vs actual spending (progress bars)
5. Income vs expenses this month (comparison chart)"

claude "Add interactive chart widgets using Chart.js or similar, with proper responsive design and color coding"
```

### Phase 4: Transaction Management
```bash
claude "Implement CSV/Excel import functionality for transactions with:
- File upload interface in Filament
- Column mapping for different bank export formats
- Duplicate detection and handling
- Preview before import confirmation"

claude "Create transaction categorization rules system:
- Pattern matching for descriptions
- Automatic category assignment
- Manual rule creation interface
- Priority-based rule application"

claude "Add transfer transaction handling - when money moves between accounts, create linked transactions that maintain balance accuracy"
```

### Phase 5: Budget & Recurring Transactions
```bash
claude "Implement budget tracking system:
- Monthly/yearly budget creation and management
- Progress tracking with visual indicators
- Alert system when approaching budget limits
- Budget vs actual reporting"

claude "Create recurring transaction automation:
- Scheduled job to process recurring transactions
- Email notifications for upcoming transactions
- Manual processing interface
- Recurring transaction templates"
```

### Phase 6: Reports & Analytics
```bash
claude "Build comprehensive financial reporting:
- Monthly/yearly expense reports with charts
- Income vs expense trends over time
- Category spending analysis
- Account balance history
- Export reports to PDF/Excel"

claude "Add advanced filtering and search:
- Date range filtering
- Multi-category filtering
- Amount range searches
- Description text search
- Account-specific views"
```

### Phase 7: Polish & UX
```bash
claude "Implement mobile-responsive design improvements:
- Optimize tables for mobile viewing
- Touch-friendly interface elements
- Responsive chart sizing
- Mobile navigation improvements"

claude "Add data backup and restore functionality:
- Export all data to JSON/CSV
- Import data from backup files
- Database cleanup tools
- Data integrity checks"
```
```bash
claude "Implement mobile-responsive design improvements:
- Optimize tables for mobile viewing
- Touch-friendly interface elements
- Responsive chart sizing
- Mobile navigation improvements"

claude "Add data backup and restore functionality:
- Export all data to JSON/CSV
- Import data from backup files
- Database cleanup tools
- Data integrity checks"
```

## Technical Specifications

### Laravel Configuration
- Laravel 10+
- PHP 8.1+
- PostgreSQL 17 database with proper indexes and constraints
- Queue system for background jobs (database or Redis)
- Proper environment configuration for Colombian locale
- Currency formatting for COP (Colombian Peso)
- Date/time formatting for Colombian timezone (America/Bogota)

### Filament Configuration
- Filament 3.x admin panel
- Default Filament theme and color scheme
- Proper navigation structure with grouped resources
- User authentication and authorization
- Multi-language support preparation

### Key Features to Implement
1. **Account Management**: Multiple account types with balance tracking
2. **Transaction Processing**: Income, expenses, and transfers
3. **Budget Planning**: Category-based budgets with alerts
4. **Recurring Transactions**: Automated transaction creation
5. **Reporting**: Charts, graphs, and export functionality
6. **Import/Export**: CSV import and PDF/Excel export
7. **Categorization**: Automatic and manual transaction categorization
8. **Security**: Proper authentication, authorization, and data encryption
9. **API**: RESTful API with authentication and mobile support

### Code Quality Requirements
- Comprehensive validation rules with Colombian formats
- Proper error handling and logging
- Unit and feature tests
- Code documentation in Spanish/English
- Optimized PostgreSQL 17 queries and indexes
- Proper use of Laravel best practices
- Colombian peso (COP) formatting and validation
- Colombian bank account number validation
- Timezone handling for America/Bogota

## File Structure
```
app/
├── Models/
│   ├── Account.php
│   ├── Category.php
│   ├── Transaction.php
│   ├── Budget.php
│   ├── RecurringTransaction.php
│   └── TransactionRule.php
├── Filament/
│   ├── Resources/
│   └── Widgets/
├── Http/
│   ├── Controllers/
│   ├── Requests/
│   ├── Resources/ (API Resources)
│   └── Middleware/
├── Jobs/
├── Policies/
└── Services/

database/
├── migrations/
├── seeders/
└── factories/

tests/
├── Feature/
└── Unit/

routes/
├── web.php
├── api.php
└── console.php
```

## Sample Commands for Claude Code

### Initial Setup
```bash
claude "Set up the basic Laravel project structure with Filament using default theme. Create the initial admin user and basic authentication"
```

### Development Workflow
```bash
# Start with core functionality
claude "Implement Phase 1: Create all migrations and models for the finance app"

# Build admin interface
claude "Implement Phase 2: Create Filament resources for all models with proper forms and validation"

# Add dashboard
claude "Implement Phase 3: Create the dashboard with financial overview widgets"

# API development
claude "Implement Phase 8: Create RESTful API with Laravel Sanctum authentication"

# Iterative improvements
claude "Add transaction import functionality with CSV parsing and duplicate detection"
claude "Implement budget tracking with progress indicators and alert system"
claude "Create financial reports with charts and export functionality"
```

### Specific Feature Requests
```bash
claude "Add a monthly spending trends chart that shows spending by category over the last 12 months"
claude "Implement automatic transaction categorization based on description patterns"
claude "Create a budget alert system that emails when spending exceeds 80% of budget"
claude "Add currency conversion support for multi-currency accounts"
claude "Create API endpoints for mobile app integration with proper authentication"
claude "Implement receipt image upload and OCR processing for automatic transaction entry"
```

