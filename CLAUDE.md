# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a Laravel API application for water utility (PAM) meter reading and billing management system called "Catat Meter API". It provides REST APIs for managing customers, meters, meter readings, billing, and reporting across multiple water utility companies (PAMs).

## Development Commands

### Laravel Setup & Development
```bash
# Install dependencies and setup project
composer run setup

# Start development server (includes API server, queue worker, logs, and Vite)
composer run dev

# Run tests
composer run test

# Individual commands
php artisan serve                    # Start Laravel development server
php artisan migrate                  # Run database migrations
php artisan tinker                   # Laravel REPL
php artisan queue:work              # Process background jobs
php artisan pail                    # View logs in real-time
npm run dev                         # Start Vite for frontend assets
npm run build                       # Build frontend assets for production
```

### Testing
- Uses Pest PHP testing framework
- Run tests: `composer run test` or `php artisan test`
- Test files are in `tests/` directory

## Architecture Overview

### API Versioning
The application has two API versions:
- **V1 API** (`/api/v1/*`): Current version with modern structure for mobile application
- **Legacy API** (`/api/*`): Original version with broader feature set

### Key Features
- Multi-PAM (water utility company) support with data isolation
- Role-based access control (SuperAdmin, Admin, Catat Meter, Pembayaran)
- Meter reading management with photo support
- Automated bill generation with tariff calculations
- Payment processing and reporting
- Customer and area management
- Export functionality for reports (Excel, PDF)

### Core Models & Relationships
- **Pam**: Water utility companies (multi-tenant)
- **Customer**: Water customers linked to PAMs and Areas
- **Meter**: Physical water meters linked to customers
- **MeterReading**: Monthly meter readings with usage calculations
- **RegisteredMonth**: Monthly periods for meter reading operations
- **Bill**: Generated bills from meter readings with payment status
- **Area**: Geographic service areas within PAMs
- **TariffGroup/TariffTier/FixedFee**: Configurable billing tariff structure

### Authentication & Authorization
- Uses Laravel Sanctum for API authentication
- Spatie Laravel Permission for role-based permissions
- Custom middleware for PAM scope filtering (`PamScopeMiddleware`)
- Role hierarchy: SuperAdmin > Admin > Catat Meter > Pembayaran

### Data Structure
- Database uses MySQL with comprehensive foreign key constraints
- Performance indexes on frequently queried columns
- Soft deletes implemented on major models
- Activity logging for audit trails

### Key Controllers Structure
```
Api/
├── V1/                    # Mobile-focused API endpoints
│   ├── AuthController.php
│   ├── CatatMeterController.php  # Month & meter reading management
│   ├── CustomerController.php
│   ├── MeterReadingController.php
│   ├── PaymentController.php
│   └── ReportController.php
└── Legacy/               # Original API endpoints with broader features
```

### Services & Repositories
- Service layer pattern implemented for business logic
- Repository pattern for data access abstraction
- Base service and repository classes for common functionality
- Custom traits for PAM filtering and API responses

### Frontend Integration
- Uses Vite for asset building
- TailwindCSS for styling
- API-first design optimized for mobile consumption

## Database Configuration
- Default database: `catat_meter_api` (MySQL)
- Migration files in `database/migrations/`
- Uses `pam_id` for multi-tenant data isolation

## Key Development Notes
- All operations are PAM-scoped using middleware for data security
- Indonesian language used in UI-facing responses
- Comprehensive validation rules on all inputs
- Optimized queries with proper indexing for performance
- Export functionality uses Maatwebsite Excel and DomPDF packages

## Testing Strategy
- Pest framework for unit and feature tests
- Test database separate from development
- API testing for all endpoints
- Coverage includes authentication, authorization, and business logic

## Environment Configuration
- Copy `.env.example` to `.env` and configure
- Default MySQL connection on localhost:3306
- Redis for caching and queue management
- File-based session storage