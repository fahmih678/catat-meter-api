# Meter Reading API Documentation

## Endpoint: GET /api/v1/meter-reading-list

Retrieves a paginated list of meter readings with advanced search and filtering capabilities, optimized for mobile applications.

### Authentication
- Requires: `Bearer Token` (Sanctum authentication)
- User must have valid PAM assignment

### Query Parameters

| Parameter | Type | Required | Description | Values |
|-----------|------|----------|-------------|---------|
| `registered_month_id` | integer | No | Filter by specific registered month | Valid registered month ID |
| `search` | string | No | Search in customer name, number, meter number, or address | Max 255 chars |
| `status` | string | No | Filter by reading status | `pending`, `completed`, `verified` |
| `area_id` | integer | No | Filter by area | Valid area ID |
| `per_page` | integer | No | Items per page | 10-100 (default: 25) |
| `page` | integer | No | Page number | Min: 1 (default: 1) |
| `sort_by` | string | No | Sort field | `customer_name`, `customer_number`, `area_name`, `meter_number`, `status`, `updated_at` |
| `sort_order` | string | No | Sort direction | `asc`, `desc` (default: desc) |

### Example Request

```bash
GET /api/v1/meter-reading-list?search=john&status=pending&per_page=20&sort_by=customer_name&sort_order=asc
Authorization: Bearer YOUR_TOKEN_HERE
```

### Response Format

```json
{
  "success": true,
  "message": "Data pencatatan meter berhasil diambil",
  "data": [
    {
      "id": 1,
      "meter_id": 123,
      "customer": {
        "name": "John Doe",
        "number": "CUST001",
        "address": "Jl. Merdeka No. 123"
      },
      "meter": {
        "number": "MTR001"
      },
      "area": {
        "name": "Area Central"
      },
      "period": {
        "month": 10,
        "year": 2024,
        "formatted": "Oktober 2024"
      },
      "readings": {
        "previous": {
          "value": 150.00,
          "formatted": "150 m³"
        },
        "current": {
          "value": 175.50,
          "formatted": "176 m³"
        },
        "volume_usage": {
          "value": 25.50,
          "formatted": "26 m³"
        }
      },
      "status": {
        "value": "pending",
        "label": "Menunggu",
        "color": "#FFA500"
      },
      "notes": "Meter in good condition",
      "last_updated": {
        "datetime": "2024-10-13 10:30:00",
        "formatted": "2 jam yang lalu",
        "date": "13 Oktober 2024 10:30"
      }
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 25,
    "total": 150,
    "last_page": 6,
    "from": 1,
    "to": 25
  },
  "summary": {
    "total_readings": 150,
    "status_counts": {
      "pending": {
        "count": 45,
        "percentage": 30.0
      },
      "completed": {
        "count": 90,
        "percentage": 60.0
      },
      "verified": {
        "count": 15,
        "percentage": 10.0
      }
    },
    "total_volume": {
      "value": 2500.50,
      "formatted": "2.501 m³"
    }
  },
  "filters": {
    "registered_month_id": null,
    "search": "john",
    "status": "pending",
    "area_id": null,
    "sort_by": "customer_name",
    "sort_order": "asc"
  }
}
```

### Error Response

```json
{
  "success": false,
  "message": "Terjadi kesalahan saat mengambil data pencatatan meter",
  "error": "Error details (in debug mode)"
}
```

### Status Codes

- `200` - Success
- `422` - Validation Error
- `401` - Unauthorized
- `500` - Server Error

### Performance Optimizations

1. **Database Indexes**: Optimized indexes on `meter_readings`, `customers`, and join tables
2. **Efficient Joins**: Single query with joins instead of N+1 queries
3. **Selective Fields**: Only fetches required fields to reduce memory usage
4. **Pagination**: Built-in pagination to handle large datasets
5. **Search Optimization**: LIKE searches with index support for MySQL

### Notes

- All dates use Indonesian locale formatting
- Currency and volume formatted with Indonesian number format
- Response includes comprehensive summary statistics
- Search is case-insensitive and supports partial matches (MySQL LIKE)
- Pagination info includes all Laravel pagination metadata