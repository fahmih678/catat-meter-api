# Catat Meter API V1 Documentation

This directory contains the Swagger/OpenAPI documentation for the Catat Meter API V1.

## File Structure

```
app/Documentation/
├── README.md                          # Updated documentation guide
├── AllDocumentation.php              # Complete API documentation (15 endpoints)
├── swagger.php                        # Main API info and server configuration (deprecated)
└── V1/                                # Previous documentation files (removed due to conflicts)

app/Http/Controllers/
├── ApiDocumentationController.php     # Custom JSON endpoint handler
└── SwaggerUiController.php            # Custom Swagger UI endpoint

resources/views/
└── swagger-ui.blade.php               # Custom Swagger UI view
```

## API Documentation Structure

The API is organized into the following main categories:

### 1. Authentication
- **POST** `/api/v1/auth/login` - User login and token generation
- **GET** `/api/v1/auth/profile` - Get authenticated user profile
- **PUT** `/api/v1/auth/profile` - Update user profile
- **POST** `/api/v1/auth/logout` - User logout

### 2. Customer Management
- **GET** `/api/v1/customers/unrecorded` - Get customers without meter readings
- **GET** `/api/v1/me/bills` - Get bills for authenticated customer

### 3. Meter Reading Operations
- **GET** `/api/v1/meter-readings/list` - Get meter readings list
- **GET** `/api/v1/customers/{id}/meter-reading-form` - Get meter reading form
- **POST** `/api/v1/meter-readings/store` - Record new meter reading
- **DELETE** `/api/v1/meter-readings/{meterReadingId}/destroy` - Delete meter reading
- **PUT** `/api/v1/meter-readings/{meterReadingId}/submit-to-pending` - Submit for billing

### 4. Payment Processing
- **GET** `/api/v1/customers/{customerId}/bills` - Get customer bills
- **POST** `/api/v1/customers/{customerId}/bills/pay` - Process bill payments
- **DELETE** `/api/v1/bills/{billId}` - Cancel bill payment

### 5. User Management (Admin/SuperAdmin)
- **GET** `/api/v1/users` - Get users list
- **GET** `/api/v1/users/{id}` - Get user details
- **PUT** `/api/v1/users/{id}` - Update user
- **POST** `/api/v1/users/{id}/assign-role` - Assign role to user
- **DELETE** `/api/v1/users/{id}/remove-role` - Remove role from user
- **DELETE** `/api/v1/users/{id}` - Delete user

### 6. PAM Management (Admin/SuperAdmin)
- **GET** `/api/v1/pams` - Get PAMs list

### 7. Registered Month Management
- **GET** `/api/v1/registered-months/list/{year}` - Get months for a year
- **POST** `/api/v1/registered-months/store` - Create new registered month
- **GET** `/api/v1/registered-months/available-months-report` - Get available months for reports

### 8. Reports
- **GET** `/api/v1/reports/monthly-payment-report` - Generate monthly payment report
- **GET** `/api/v1/reports/download-payment-report` - Download payment report (Excel/PDF)
- **POST** `/api/v1/sync/payment-summary/{registeredMonthId}` - Sync payment summary data

## Security and Authentication

The API uses Laravel Sanctum for authentication. All protected endpoints require a valid Bearer token:

```
Authorization: Bearer {token}
```

### Role-Based Access Control

The API implements role-based access control with the following roles:

- **SuperAdmin**: Full system access
- **Admin**: PAM-level management
- **Catat Meter**: Meter reading operations
- **Loket**: Payment processing
- **Customer**: View own bills

## Response Format

All API responses follow a consistent format:

### Success Response
```json
{
  "status": "success",
  "message": "Operation successful",
  "data": {
    // Response data
  }
}
```

### Error Response
```json
{
  "status": "error",
  "message": "Error occurred",
  "errors": {
    // Validation errors (if applicable)
  }
}
```

### Paginated Response
```json
{
  "status": "success",
  "message": "Data retrieved successfully",
  "data": {
    "items": [...],
    "total": 100,
    "has_more_pages": true
  }
}
```

## Accessing the Documentation

### Swagger UI (Working)
Access the interactive Swagger documentation at:
```
http://localhost:8000/api/docs/ui
```

### API Documentation JSON (Working)
Access the raw OpenAPI JSON at:
```
http://localhost:8000/api/docs
```

### Original L5 Swagger UI (Not Working - Route Issues)
The original L5 Swagger UI has routing issues:
```
http://localhost:8000/api/documentation  # Currently experiencing route errors
```

## Development

### Adding New Documentation

1. Create new documentation file in appropriate `V1/` subdirectory
2. Extend `App\Documentation\Components\Responses` for common schemas
3. Use OpenAPI annotations (`@OA\`) for documentation
4. Follow the existing patterns for consistency

### Common Annotation Patterns

#### Endpoint Documentation
```php
/**
 * @OA\Get(
 *     path="/api/v1/endpoint",
 *     tags={"Tag Name"},
 *     summary="Brief description",
 *     description="Detailed description",
 *     operationId="uniqueOperationId",
 *     security={{"sanctum": {}}},
 *
 *     @OA\Parameter(...),
 *     @OA\Response(...)
 * )
 */
```

#### Request Body
```php
*     @OA\RequestBody(
*         required=true,
*         @OA\JsonContent(
*             required={"field1","field2"},
*             @OA\Property(property="field1", type="string", example="value1"),
*             @OA\Property(property="field2", type="integer", example=123)
*         )
*     )
```

#### Response
```php
*     @OA\Response(
*         response=200,
*         description="Successful operation",
*         @OA\JsonContent(
*             @OA\Property(property="status", type="string", example="success"),
*             @OA\Property(property="message", type="string", example="Operation successful"),
*             @OA\Property(property="data", type="object", ...)
*         )
*     )
```

### Generating Documentation

To generate/update the Swagger documentation:

```bash
php artisan l5-swagger:generate
```

For development with auto-generation:
```bash
# Set L5_SWAGGER_GENERATE_ALWAYS=true in .env
# Or use:
L5_SWAGGER_GENERATE_ALWAYS=true php artisan l5-swagger:generate
```

## Notes

- All datetime fields use ISO 8601 format: `YYYY-MM-DD HH:MM:SS`
- All currency amounts are in Indonesian Rupiah (IDR)
- File uploads use multipart/form-data
- API uses UTC timezone for all timestamps
- All text responses are in Indonesian language