# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a Laravel API application for water utility (PAM) meter reading and billing management system called "Catat Meter API". It provides REST APIs for managing customers, meters, meter readings, billing, and reporting across multiple water utility companies (PAMs) with a comprehensive multi-tenant architecture.

## Development Commands

### Laravel Setup & Development
```bash
# Complete project setup (install dependencies, copy .env, migrate, build assets)
composer run setup

# Start full development environment (API server, queue worker, logs, Vite)
composer run dev

# Run tests with configuration cache clear
composer run test

# Individual development commands
php artisan serve                    # Start Laravel development server (http://localhost:8000)
php artisan migrate                  # Run database migrations
php artisan migrate:fresh           # Fresh database with all migrations
php artisan tinker                   # Laravel REPL for testing
php artisan queue:work              # Process background jobs
php artisan queue:listen --tries=1 # Queue listener with auto-retry
php artisan pail                    # View real-time application logs
npm run dev                         # Start Vite development server for assets
npm run build                       # Build production assets
```

### API Documentation
```bash
# Generate Swagger API documentation
php artisan l5-swagger:generate

# Access API documentation
# Swagger UI: http://localhost:8000/docs/ui
# API Docs JSON: http://localhost:8000/docs/api-docs.json
```

### Testing & Quality Assurance
- Uses **Pest PHP** testing framework with Laravel plugin
- Test files located in `tests/Feature/` and `tests/Unit/` directories
- Separate test database with automated migrations
- API testing covers all endpoints with authentication and authorization

**Test Commands:**
```bash
composer run test              # Run all tests with optimized configuration
php artisan test               # Alternative test command
php artisan test --filter=AuthTest  # Run specific test class
php artisan test --group=api   # Run tests in specific group
```

## Architecture Overview

### API Versioning Strategy
The application implements comprehensive API versioning with distinct separation:

- **V1 API** (`/api/v1/*`): Modern mobile-focused API with **27 endpoints** across 8 modules
  - AuthController, CustomerController, MeterReadingController (most complex at 22.9KB)
  - PaymentController, ReportController (21.0KB), UserController, PamController
  - RegisteredMonthController for billing period management
- **Legacy API** (`/api/*`): Original version with broader feature set for web interfaces

### Core Business Features
- **Multi-PAM Architecture**: Complete data isolation between water utility companies using `pam_id`
- **Role-Based Access Control**: Hierarchical permissions (SuperAdmin > Admin > Catat Meter > Pembayaran > Customer)
- **Meter Reading Management**: Photo support, usage calculations, validation rules
- **Automated Billing**: Tariff calculations with configurable rates and fixed fees
- **Payment Processing**: Comprehensive payment tracking and status management
- **Export Capabilities**: Excel and PDF generation for reports (Maatwebsite Excel + DomPDF)
- **Real-time Monitoring**: Activity logging and audit trails

### Database Schema & Core Models
**Multi-tenant data model with comprehensive relationships:**

- **Pam**: Water utility companies (root multi-tenant entity)
- **User**: Multi-role users with PAM association and hierarchical permissions
- **Customer**: Water customers linked to PAMs and geographic Areas
- **Meter**: Physical water meters with customer association
- **MeterReading**: Monthly readings with photo support and usage calculations
- **RegisteredMonth**: Billing periods for meter reading operations
- **Bill**: Generated bills with payment status and tariff calculations
- **Area**: Geographic service areas within PAMs
- **TariffStructure**: Configurable billing rates (TariffGroup/TariffTier/FixedFee)

**Key Relationships:**
```
Pam → User (1:N) → Pam → Customer (1:N) → Meter (1:N) → MeterReading (1:N)
RegisteredMonth → Bill (1:N) ← Customer
User → MeterReading (recorded_by)
```

### Authentication & Authorization Stack
- **Laravel Sanctum**: API token-based authentication
- **Spatie Laravel Permission**: Role-based access control with permissions
- **Custom Middleware**: `PamScopeMiddleware` for automatic data filtering
- **Role Hierarchy**: SuperAdmin (system-wide) → Admin (PAM management) → Catat Meter (operations) → Pembayaran (payments)

**Middleware Flow:**
```
auth:sanctum → role:* → pam.scope → role:* → pam.scope
```

### Service Layer Architecture
**Business logic abstraction with clean separation:**

- **Services** (`app/Services/`): Business logic layer
  - BaseService: Common functionality and error handling
  - AuthService, CustomerService, MeterReadingService, PaymentService
  - ReportService with export functionality
- **Repositories** (`app/Repositories/`): Data access abstraction
  - BaseRepository: Common CRUD operations and PAM filtering
  - Domain-specific repositories with optimized queries
- **Traits**: PamScopeTrait, ApiResponseTrait for reusable functionality

### Controller Structure
```
app/Http/Controllers/
├── Api/V1/                      # Modern mobile API (27 endpoints)
│   ├── AuthController.php (8.5KB)
│   ├── CustomerController.php (14.2KB)
│   ├── MeterReadingController.php (22.9KB) ← Most complex
│   ├── PaymentController.php (11.8KB)
│   ├── ReportController.php (21.0KB)
│   ├── UserController.php (17.2KB)
│   ├── PamController.php (1.9KB)
│   └── RegisteredMonthController.php (8.3KB)
├── Api/Legacy/                  # Original API with broader features
├── ApiDocumentationController.php
└── SwaggerUiController.php
```

### API Response Standards
**Consistent response format using ApiResponseTrait:**
```json
{
  "success": true/false,
  "message": "Descriptive message",
  "data": {...},
  "errors": {...}
}
```

### Frontend Integration
- **Vite**: Modern asset build system with hot module replacement
- **TailwindCSS**: Utility-first CSS framework for styling
- **API-first Design**: Optimized for mobile consumption with RESTful endpoints

## Database & Performance Configuration

### Database Setup
- **Default Database**: `catat_meter_api` (MySQL)
- **Migrations**: Located in `database/migrations/` with comprehensive foreign key constraints
- **Multi-tenant Isolation**: All entities use `pam_id` for data segregation
- **Performance**: Strategic indexes on frequently queried columns (`pam_id`, customer relationships)
- **Soft Deletes**: Implemented on major models for data recovery

### Caching & Queue Strategy
- **Redis**: Primary cache and queue driver with database fallback
- **Database Queue**: Background job processing for heavy operations
- **Query Optimization**: Eager loading and proper indexing for performance

## Development Best Practices

### Code Standards & Patterns
- **PSR-12 Compliance**: Standardized PHP formatting with Laravel conventions
- **Input Validation**: Comprehensive request validation using Form Request classes
- **Error Handling**: Centralized exception handling with appropriate HTTP status codes
- **Security**: PAM-based data isolation, role-based permissions, input sanitization
- **API Design**: RESTful endpoints with consistent response formats

### Multi-Tenant Security
- **PAM Scope Middleware**: Automatic data filtering for non-SuperAdmin users
- **Role-Based Access**: Hierarchical permissions with context-aware authorization
- **Data Isolation**: Complete separation between water utility companies
- **Audit Trails**: Activity logging for sensitive operations

### Performance Considerations
- **Database Optimization**: Proper indexing, query optimization, relationship loading
- **Background Processing**: Queue jobs for heavy operations (exports, bill generation)
- **Caching Strategy**: Redis for frequent data with database fallback
- **Asset Optimization**: Vite production builds with minification

## Testing Strategy

### Comprehensive Test Coverage
- **Framework**: Pest PHP with Laravel plugin for modern test syntax
- **Test Types**: Unit tests for components, Feature tests for workflows, API tests for endpoints
- **Database**: Separate test database with automated migrations and cleanup
- **Coverage Areas**: Authentication, authorization, business logic, API contracts, data validation

### Test Commands & Structure
```bash
composer run test                    # Optimized test runner
php artisan test --filter=AuthTest   # Specific test classes
php artisan test --group=api        # Group-based test execution
```

## Environment & Deployment

### Local Development Setup
```bash
cp .env.example .env                # Environment configuration
# Configure: DB_DATABASE, DB_USERNAME, DB_PASSWORD
php artisan key:generate            # Application encryption key
php artisan migrate                 # Database setup
```

### Key Environment Variables
- **Database**: MySQL connection (default: localhost:3306)
- **Cache**: Redis configuration with database fallback
- **Queue**: Database queue processing
- **Session**: Database-based session storage
- **File Storage**: Local disk with public access for uploads

### API Documentation Access
- **Swagger UI**: `http://localhost:8000/docs/ui` (interactive documentation)
- **API Docs JSON**: `http://localhost:8000/docs/api-docs.json` (machine-readable)
- **Postman Collection**: Available through API documentation exports