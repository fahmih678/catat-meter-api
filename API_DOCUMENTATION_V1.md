# API Documentation v1

## Base URL
```
https://your-domain.com/api/v1
```

## Authentication
All protected endpoints require Bearer token authentication.

### Headers
```
Authorization: Bearer {your_token}
Content-Type: application/json
Accept: application/json
```

## Response Format
All API responses follow this standard format:

### Success Response
```json
{
    "status": "success",
    "message": "Description of the action",
    "data": {
        // Response data here
    }
}
```

### Error Response
```json
{
    "status": "error",
    "message": "Error description",
    "code": "ERROR_CODE",
    "errors": {
        // Validation errors (if applicable)
    }
}
```

## Authentication Endpoints

### POST /auth/login
Login and get access token.

**Request Body:**
```json
{
    "email": "user@example.com",
    "password": "password",
    "device_name": "mobile-app" // optional
}
```

**Response:**
```json
{
    "status": "success",
    "message": "Login successful",
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "user@example.com",
            "role": "catat_meter",
            "pam_id": 1
        },
        "token": "1|abc123...",
        "token_type": "Bearer"
    }
}
```

### GET /auth/profile
Get authenticated user profile.

### PUT /auth/profile
Update user profile.

### POST /auth/logout
Logout and revoke current token.

### POST /auth/logout-all
Logout from all devices.

### POST /auth/refresh
Refresh access token.

### GET /auth/check-token
Check if current token is valid.

## Meter Endpoints

### GET /meters
Get list of meters for user's PAM.

**Query Parameters:**
- `per_page` (int): Items per page (default: 15)
- `search` (string): Search term
- `status` (string): Filter by status
- `area_id` (int): Filter by area

### GET /meters/my-meters
Get meters assigned to current user for reading.

**Query Parameters:**
- `date` (date): Target date (default: today)

### GET /meters/daily-summary
Get daily reading summary for current user.

### GET /meters/{id}
Get specific meter details.

### POST /meters/{id}/reading
Record meter reading.

**Request Body:**
```json
{
    "current_reading": 150,
    "reading_date": "2024-10-11",
    "photo": "base64_image_or_file", // optional
    "notes": "Normal reading" // optional
}
```

### PUT /meters/{meterId}/reading/{recordId}
Update meter reading (only same day, same user).

### GET /meters/{id}/history
Get meter reading history.

## Customer Endpoints

### GET /customers
Get list of customers.

**Query Parameters:**
- `per_page` (int): Items per page
- `search` (string): Search term
- `status` (string): Filter by status
- `area_id` (int): Filter by area

### POST /customers
Create new customer (Admin only).

### GET /customers/search
Search customers.

**Query Parameters:**
- `q` (string): Search query (required)
- `limit` (int): Max results (default: 10)

### GET /customers/area/{areaId}
Get customers by area.

### GET /customers/{id}
Get specific customer details.

### PUT /customers/{id}
Update customer (Admin only).

### DELETE /customers/{id}
Delete customer (Admin only).

### GET /customers/{id}/meters
Get customer's meters.

### GET /customers/{id}/billing-history
Get customer's billing history.

## Payment Endpoints

### GET /payments/dashboard
Get payment dashboard data (Payment staff only).

### GET /payments/bills
Get list of bills.

**Query Parameters:**
- `per_page` (int): Items per page
- `search` (string): Search term
- `status` (string): Filter by status (unpaid, paid, overdue)

### GET /payments/bills/{id}
Get specific bill details.

### POST /payments/bills/{id}/pay
Process payment for a bill.

**Request Body:**
```json
{
    "payment_method": "cash", // cash, transfer, card
    "amount_paid": 77500,
    "notes": "Cash payment" // optional
}
```

### GET /payments/history
Get payment history.

### POST /payments/generate-bill
Generate customer bill (Admin only).

### GET /payments/daily-report
Get daily payment report.

## Error Codes

- `UNAUTHENTICATED`: User not authenticated
- `INVALID_TOKEN`: Token is invalid
- `TOKEN_EXPIRED`: Token has expired
- `INSUFFICIENT_PERMISSIONS`: User doesn't have required role
- `VALIDATION_ERROR`: Request validation failed
- `NOT_FOUND`: Resource not found
- `ALREADY_EXISTS`: Resource already exists

## Status Codes

- `200`: Success
- `201`: Created
- `400`: Bad Request
- `401`: Unauthorized
- `403`: Forbidden
- `404`: Not Found
- `409`: Conflict
- `422`: Unprocessable Entity
- `500`: Internal Server Error

## Rate Limiting

API requests are limited to:
- 60 requests per minute for authenticated users
- 10 requests per minute for unauthenticated endpoints

When rate limit is exceeded, the API returns status code `429` with headers:
- `X-RateLimit-Limit`: Request limit
- `X-RateLimit-Remaining`: Remaining requests
- `X-RateLimit-Reset`: Reset time (Unix timestamp)

## Testing with Postman/Insomnia

1. **Login**: POST `/api/v1/auth/login` to get token
2. **Set Headers**: Add `Authorization: Bearer {token}` to all requests
3. **Test Endpoints**: Use the endpoints documented above

## Mobile App Integration

For mobile applications:
1. Use device-specific `device_name` when logging in
2. Store token securely (Android Keystore/iOS Keychain)
3. Implement token refresh logic
4. Handle offline scenarios gracefully
5. Use appropriate error handling for network issues