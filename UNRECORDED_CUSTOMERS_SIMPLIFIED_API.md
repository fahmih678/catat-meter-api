# API Customer Belum Tercatat - Response Format Terbaru

## Endpoint
```
GET /api/v1/unrecorded-customers
```

## Parameters
- `registered_month_id` (required): ID bulan yang terdaftar
- `area_id` (optional): Filter berdasarkan area
- `search` (optional): Pencarian nama/nomor customer/alamat/nomor meter
- `per_page` (optional): Jumlah data per halaman (10-100, default: 25)
- `page` (optional): Halaman (default: 1)
- `sort_by` (optional): Field sorting (`customer_name`, `customer_number`, `area_name`, `meter_number`)
- `sort_order` (optional): Urutan (`asc`, `desc`)

## Response Format

### Success Response (200 OK)
```json
{
  "data": [
    {
      "name": "John Doe",
      "number": "C001", 
      "address": "Jl. ABC 123",
      "meter": {
        "number": "M001"
      }
    }
  ],
  "pagination": {
    "total": 100,
    "hasNextPage": true
  },
  "summary": {
    "unrecorded": 25
  }
}
```

### Error Response (4xx/5xx)
```json
{
  "data": [],
  "pagination": {
    "total": 0,
    "hasNextPage": false
  },
  "summary": {
    "unrecorded": 0
  }
}
```

## Field Descriptions

### Data Array
- `name`: Nama customer
- `number`: Nomor customer (unique identifier)
- `address`: Alamat lengkap customer
- `meter.number`: Nomor meter

### Pagination Object
- `total`: Total data yang tersedia
- `hasNextPage`: Boolean apakah ada halaman selanjutnya

### Summary Object
- `unrecorded`: Jumlah customer yang belum tercatat untuk area/filter yang diminta

## Example Usage

### Basic Request
```bash
curl -X GET "http://localhost:8000/api/v1/unrecorded-customers?registered_month_id=1" \
  -H "Authorization: Bearer {token}"
```

### With Filters
```bash
curl -X GET "http://localhost:8000/api/v1/unrecorded-customers?registered_month_id=1&area_id=1&search=John&per_page=20" \
  -H "Authorization: Bearer {token}"
```

## Response Examples

### Search Result
```json
{
  "data": [
    {
      "name": "Bambang Permana",
      "number": "SWTBK0005",
      "address": "Jl. Sudirman No. 207, RT 15/RW 8, Zona A - Perumahan Elite",
      "meter": {
        "number": "SWTBK25466086"
      }
    }
  ],
  "pagination": {
    "total": 2,
    "hasNextPage": false
  },
  "summary": {
    "unrecorded": 33
  }
}
```

### Area Filter Result
```json
{
  "data": [
    {
      "name": "Bambang Permana",
      "number": "SWTBK0005",
      "address": "Jl. Sudirman No. 207, RT 15/RW 8, Zona A - Perumahan Elite", 
      "meter": {
        "number": "SWTBK25466086"
      }
    }
  ],
  "pagination": {
    "total": 7,
    "hasNextPage": false
  },
  "summary": {
    "unrecorded": 7
  }
}
```

## Notes
- Response format disederhanakan sesuai kebutuhan frontend
- Summary `unrecorded` akan update sesuai dengan filter yang digunakan (area_id)
- Semua error response menggunakan format yang sama dengan data kosong
- Pagination `hasNextPage` menunjukkan apakah perlu load more data