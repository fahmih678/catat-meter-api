# API Customer Belum Tercatat - Dokumentasi Lengkap

## Overview
API untuk mendapatkan daftar customer yang belum tercatat meter reading berdasarkan PAM ID dan area ID pada bulan tertentu.

## Endpoint
```
GET /api/v1/unrecorded-customers
```

## Authentication
Menggunakan Bearer Token (Sanctum)
```
Authorization: Bearer {token}
```

## Query Parameters

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `registered_month_id` | integer | **Yes** | - | ID dari registered month |
| `area_id` | integer | No | null | Filter berdasarkan area tertentu |
| `search` | string | No | null | Pencarian customer name, number, address, atau meter number |
| `per_page` | integer | No | 25 | Jumlah data per halaman (min: 10, max: 100) |
| `page` | integer | No | 1 | Halaman yang akan ditampilkan |
| `sort_by` | string | No | customer_name | Field untuk sorting: `customer_name`, `customer_number`, `area_name`, `meter_number` |
| `sort_order` | string | No | asc | Urutan sorting: `asc`, `desc` |

## Response Format

### Success Response (200 OK)
```json
{
  "success": true,
  "message": "Data customer belum tercatat berhasil diambil",
  "data": [
    {
      "id": 5,
      "name": "Bambang Permana",
      "number": "SWTBK0005",
      "address": "Jl. Sudirman No. 207, RT 15/RW 8, Zona A - Perumahan Elite",
      "phone": "082166561884",
      "area": {
        "id": 1,
        "name": "Zona A - Perumahan Elite"
      },
      "meter": {
        "id": 5,
        "number": "SWTBK25466086",
        "initial_reading": {
          "value": "19.00",
          "formatted": "19 m³"
        },
        "installed_at": {
          "datetime": "2023-03-02 10:59:59",
          "formatted": "02 Maret 2023"
        }
      },
      "tariff_group": "Sosial",
      "period": {
        "id": 1,
        "month": 10,
        "year": 2024,
        "formatted": "Oktober 2024"
      },
      "status": {
        "value": "unrecorded",
        "label": "Belum Tercatat",
        "color": "#FF5722"
      }
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 10,
    "total": 33,
    "last_page": 4,
    "from": 1,
    "to": 10
  },
  "summary": {
    "total_with_meters": 155,
    "recorded": 122,
    "unrecorded": 33,
    "completion_percentage": 78.7,
    "area_breakdown": [
      {
        "area_id": 1,
        "area_name": "Zona A - Perumahan Elite",
        "unrecorded_count": 7
      }
    ]
  },
  "period": {
    "id": 1,
    "formatted": "Oktober 2024",
    "status": "closed"
  },
  "filters": {
    "registered_month_id": "1",
    "area_id": null,
    "search": null,
    "sort_by": "customer_name",
    "sort_order": "asc"
  }
}
```

### Error Responses

#### 404 - Registered Month Not Found
```json
{
  "success": false,
  "message": "Registered month tidak ditemukan atau tidak sesuai dengan PAM Anda"
}
```

#### 422 - Validation Error
```json
{
  "success": false,
  "message": "Terjadi kesalahan saat mengambil data customer belum tercatat",
  "error": "The per page field must be at least 10."
}
```

#### 500 - Server Error
```json
{
  "success": false,
  "message": "Terjadi kesalahan saat mengambil data customer belum tercatat",
  "error": "Internal server error"
}
```

## Example Usage

### 1. Basic Request - Semua Customer Belum Tercatat
```bash
curl -X GET "http://localhost:8000/api/v1/unrecorded-customers?registered_month_id=1" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {your_token}"
```

### 2. Filter by Area
```bash
curl -X GET "http://localhost:8000/api/v1/unrecorded-customers?registered_month_id=1&area_id=1" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {your_token}"
```

### 3. Search Customer
```bash
curl -X GET "http://localhost:8000/api/v1/unrecorded-customers?registered_month_id=1&search=Bambang" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {your_token}"
```

### 4. Pagination & Sorting
```bash
curl -X GET "http://localhost:8000/api/v1/unrecorded-customers?registered_month_id=1&per_page=20&page=2&sort_by=area_name&sort_order=desc" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {your_token}"
```

### 5. Combined Filters
```bash
curl -X GET "http://localhost:8000/api/v1/unrecorded-customers?registered_month_id=1&area_id=1&search=Permana&per_page=10&sort_by=customer_name" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {your_token}"
```

## Data Structure Details

### Customer Data
- `id`: ID customer
- `name`: Nama customer
- `number`: Nomor customer (unique)
- `address`: Alamat lengkap customer
- `phone`: Nomor telepon (bisa null)

### Area Data
- `id`: ID area
- `name`: Nama area

### Meter Data
- `id`: ID meter
- `number`: Nomor meter (unique)
- `initial_reading`: 
  - `value`: Nilai angka awal meter (string format)
  - `formatted`: Format display (contoh: "19 m³")
- `installed_at`:
  - `datetime`: Tanggal pasang meter (format: Y-m-d H:i:s)
  - `formatted`: Format Indonesian (contoh: "02 Maret 2023")

### Period Data
- `id`: ID registered month
- `month`: Bulan (integer 1-12)
- `year`: Tahun (integer)
- `formatted`: Format Indonesian (contoh: "Oktober 2024")

### Status Data
- `value`: "unrecorded" (fixed value)
- `label`: "Belum Tercatat" (Indonesian label)
- `color`: "#FF5722" (Deep Orange color for UI)

### Summary Statistics
- `total_with_meters`: Total customer yang memiliki meter aktif
- `recorded`: Jumlah customer yang sudah tercatat
- `unrecorded`: Jumlah customer yang belum tercatat
- `completion_percentage`: Persentase completion (recorded/total * 100)
- `area_breakdown`: Array breakdown per area dengan unrecorded_count

## Business Logic

### Customer Selection Criteria
1. Customer harus aktif (`is_active = true`)
2. Customer harus memiliki meter aktif (`meters.is_active = true`)
3. Customer harus dalam PAM yang sama dengan user yang login
4. Tidak ada meter reading untuk registered_month_id yang diminta

### Query Performance
- Menggunakan `whereNotExists` untuk efisiensi query
- Index pada `meter_readings.registered_month_id` dan `meter_readings.meter_id`
- Join optimized untuk menghindari N+1 queries

### Security
- Data dibatasi berdasarkan PAM user yang login
- Validation untuk semua input parameters
- Sanitized search untuk mencegah SQL injection

## Testing dengan Data Seeder

### Available Test Data
- 3 PAM dengan masing-masing 5 area
- 626 meter readings tersebar di 15 registered months
- Customer dengan berbagai status meter reading

### Test Scenarios
1. **No Results**: Request untuk bulan yang semua customer sudah tercatat
2. **Partial Results**: Request untuk bulan dengan beberapa customer belum tercatat
3. **Area Filter**: Filter berdasarkan area tertentu
4. **Search**: Pencarian berdasarkan nama, nomor, atau alamat
5. **Pagination**: Test dengan per_page dan page berbeda

### Sample Test Token
```bash
# Create test token
php artisan tinker --execute="
echo 'Test Token: '; 
\$user = App\Models\User::where('email', 'catat1@gmail.com')->first(); 
echo \$user->createToken('test-api')->plainTextToken;
"
```

## UI Integration Tips

### For Mobile App
1. Gunakan `summary.completion_percentage` untuk progress bar
2. `area_breakdown` untuk dropdown filter area
3. `status.color` untuk consistent UI colors
4. `formatted` fields untuk display yang user-friendly

### Error Handling
1. Handle 404 untuk registered month tidak valid
2. Handle 422 untuk validation errors
3. Show appropriate loading states
4. Implement retry logic untuk network errors

### Performance Tips
1. Implement infinite scroll dengan pagination
2. Cache area list untuk filter dropdown
3. Debounce search input (300-500ms)
4. Show summary statistics sebagai overview

## Next Steps
1. Implement meter reading creation untuk customer belum tercatat
2. Add bulk operations untuk multiple customers
3. Add export functionality (Excel/PDF)
4. Implement push notifications untuk reminder pencatatan