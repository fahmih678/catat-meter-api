# API Meter Input Data - Dokumentasi

## Overview
API untuk mendapatkan data customer dan meter sebelum melakukan input meter reading. API ini memberikan informasi lengkap customer, area, meter, dan pembacaan terakhir.

## Endpoint
```
GET /api/v1/customers/{id}/meter-input-data
```

## Authentication
Menggunakan Bearer Token (Sanctum)
```
Authorization: Bearer {token}
```

## Parameters

### Path Parameters
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | **Yes** | ID customer yang akan diinput meter reading-nya |

## Response Format

### Success Response (200 OK)
```json
{
  "status": "success",
  "data": {
    "customer_id": 2,
    "name": "Putri Susanto",
    "number": "SWTBK0002", 
    "address": "Jl. Pahlawan No. 923, RT 6/RW 5, Zona D - Industri",
    "area": {
      "id": 4,
      "name": "Zona D - Industri"
    },
    "meter": {
      "number": "SWTBK25141260",
      "last_reading": 476.86,
      "last_reading_at": "2025-10-14"
    },
    "pam": {
      "name": "Sumber Waras Tuban Kulon"
    }
  }
}
```

### Error Responses

#### 404 - Customer Not Found
```json
{
  "status": "error",
  "message": "Customer tidak ditemukan atau tidak sesuai dengan PAM Anda"
}
```

#### 404 - No Active Meter
```json
{
  "status": "error",
  "message": "Customer tidak memiliki meter aktif"
}
```

#### 500 - Server Error
```json
{
  "status": "error",
  "message": "Terjadi kesalahan saat mengambil data meter input"
}
```

## Field Descriptions

### Customer Data
- `customer_id`: ID customer (integer)
- `name`: Nama lengkap customer
- `number`: Nomor customer (unique identifier)
- `address`: Alamat lengkap customer

### Area Data
- `id`: ID area (integer)
- `name`: Nama area/zona

### Meter Data
- `number`: Nomor meter (unique identifier)
- `last_reading`: Nilai pembacaan terakhir (float)
  - Jika ada meter reading sebelumnya: menggunakan `current_reading` dari meter reading terakhir
  - Jika belum pernah ada meter reading: menggunakan `initial_installed_meter` dari data meter
- `last_reading_at`: Tanggal pembacaan terakhir (YYYY-MM-DD format)
  - Jika ada meter reading sebelumnya: tanggal created_at dari meter reading terakhir
  - Jika belum pernah ada meter reading: tanggal installed_at dari data meter

### PAM Data
- `name`: Nama PAM (Perusahaan Air Minum)

## Business Logic

### Customer Selection Criteria
1. Customer harus ada dan sesuai ID yang diminta
2. Customer harus dalam PAM yang sama dengan user yang login
3. Customer harus aktif (`is_active = true`)
4. Customer harus memiliki meter aktif

### Last Reading Logic
1. **Ada Meter Reading Sebelumnya**: 
   - `last_reading` = `current_reading` dari meter reading paling terbaru
   - `last_reading_at` = `created_at` dari meter reading paling terbaru
2. **Belum Ada Meter Reading**:
   - `last_reading` = `initial_installed_meter` dari data meter
   - `last_reading_at` = `installed_at` dari data meter

### Security
- Data customer dibatasi berdasarkan PAM user yang login
- User tidak dapat mengakses data customer dari PAM lain
- Validasi customer aktif dan meter aktif

## Example Usage

### Basic Request
```bash
curl -X GET "http://localhost:8000/api/v1/customers/2/meter-input-data" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {your_token}"
```

### Response Examples

#### Customer dengan Meter Reading History
```json
{
  "status": "success",
  "data": {
    "customer_id": 2,
    "name": "Putri Susanto",
    "number": "SWTBK0002",
    "address": "Jl. Pahlawan No. 923, RT 6/RW 5, Zona D - Industri",
    "area": {
      "id": 4,
      "name": "Zona D - Industri"
    },
    "meter": {
      "number": "SWTBK25141260",
      "last_reading": 476.86,
      "last_reading_at": "2025-10-14"
    },
    "pam": {
      "name": "Sumber Waras Tuban Kulon"
    }
  }
}
```

#### Customer Tanpa History (Menggunakan Initial Reading)
```json
{
  "status": "success",
  "data": {
    "customer_id": 150,
    "name": "John Doe",
    "number": "SWTBK0150",
    "address": "Jl. Merdeka No. 123",
    "area": {
      "id": 1,
      "name": "Zona A - Perumahan Elite"
    },
    "meter": {
      "number": "SWTBK25000123",
      "last_reading": 0.0,
      "last_reading_at": "2023-01-15"
    },
    "pam": {
      "name": "Sumber Waras Tuban Kulon"
    }
  }
}
```

## Integration Tips

### For Mobile App
1. Gunakan data ini untuk pre-populate form input meter reading
2. Tampilkan `last_reading` sebagai referensi pembacaan sebelumnya  
3. Validasi input baru harus >= `last_reading`
4. Tampilkan tanggal `last_reading_at` untuk context

### Form Validation
1. Input meter reading baru harus >= `last_reading`
2. Validasi reasonable increase (misalnya max 100 m³ per bulan)
3. Tampilkan warning jika ada perubahan yang drastis

### Error Handling
1. Handle case customer tidak ditemukan dengan user-friendly message
2. Handle case customer tidak punya meter aktif
3. Implement retry untuk network errors
4. Cache data untuk offline capability (optional)

## Use Case Flow
1. **Petugas buka form input meter reading**
2. **Scan QR code customer atau pilih dari list**
3. **Call API ini untuk mendapatkan data customer dan last reading**
4. **Pre-populate form dengan data yang didapat**
5. **Petugas input current reading baru**
6. **Submit ke API create meter reading**

## Testing

### Available Test Data
- Customer ID 2: Putri Susanto (ada history meter reading)
- Customer ID 5: Maya Pratama (ada history meter reading)
- Customer ID 3: Fitri Sari (tidak punya meter aktif)

### Test Token
```bash
# Create test token
php artisan tinker --execute="
echo 'Test Token: '; 
\$user = App\Models\User::where('email', 'catat1.SWTBK@example.com')->first(); 
echo \$user->createToken('test-api')->plainTextToken;
"
```

### Sample Test Commands
```bash
# Valid customer dengan meter reading history
curl -X GET "http://localhost:8000/api/v1/customers/2/meter-input-data" \
  -H "Authorization: Bearer {token}"

# Customer tanpa meter aktif (error)
curl -X GET "http://localhost:8000/api/v1/customers/3/meter-input-data" \
  -H "Authorization: Bearer {token}"

# Customer dari PAM lain (error - security test)  
curl -X GET "http://localhost:8000/api/v1/customers/201/meter-input-data" \
  -H "Authorization: Bearer {token}"
```