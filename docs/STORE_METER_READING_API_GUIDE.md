# API Store Meter Reading - Dokumentasi Lengkap

## Overview
API untuk menyimpan meter reading baru dengan dukungan upload image. API ini dapat menangani data dalam format JSON (tanpa image) atau form-data (dengan image).

## Endpoint
```
POST /api/v1/store-meter-reading
```

## Authentication
Menggunakan Bearer Token (Sanctum)
```
Authorization: Bearer {token}
```

## Request Format

### Option 1: JSON (Tanpa Image)
```
Content-Type: application/json
```

### Option 2: Form Data (Dengan Image)
```
Content-Type: multipart/form-data
```

## Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `customer_id` | integer | **Yes** | ID customer yang akan diinput meter reading-nya |
| `registered_month_id` | integer | **Yes** | ID registered month untuk periode pencatatan |
| `current_reading` | decimal | **Yes** | Nilai pembacaan meter saat ini |
| `notes` | string | No | Catatan tambahan (max 1000 karakter) |
| `reading_at` | date | **Yes** | Tanggal pembacaan meter (YYYY-MM-DD) |
| `photo` | file | No | File gambar meter (JPEG/PNG, max 5MB) |

## Business Logic

### Automatic Calculations
1. **Previous Reading**: Diambil dari meter reading terakhir atau initial meter value
2. **Volume Usage**: `current_reading - previous_reading` (otomatis dihitung)
3. **Reading By**: User yang sedang login (otomatis diisi)
4. **PAM ID**: PAM user yang sedang login (otomatis diisi)
5. **Status**: Default "pending"

### Validations
1. **Current Reading Validation**: Harus >= previous reading
2. **Meter Active**: Customer harus memiliki meter aktif
3. **Image Validation**: JPEG/PNG, max 5MB
4. **Date Format**: YYYY-MM-DD

### Image Upload
- **Storage Path**: `storage/app/public/meter_readings/YYYY/MM/`
- **Filename Format**: `meter_reading_{customer_id}_{timestamp}_{random}.{ext}`
- **Public URL**: `/storage/meter_readings/YYYY/MM/filename`
- **Allowed Types**: JPEG, JPG, PNG
- **Max Size**: 5MB

## Response Format

### Success Response (201 Created)
```json
{
  "status": "success",
  "message": "Meter reading berhasil disimpan",
  "data": {
    "id": 6210,
    "current_reading": "600.75",
    "volume_usage": "110.45",
    "photo_url": "/storage/meter_readings/2025/10/meter_reading_138_1760434957_QI6oRJiigH.png",
    "reading_at": "2024-10-14"
  }
}
```

### Error Responses

#### 404 - Meter Not Found
```json
{
  "status": "error",
  "message": "Meter tidak ditemukan atau tidak aktif"
}
```

#### 422 - Validation Error (Current Reading)
```json
{
  "status": "error",
  "message": "Pembacaan saat ini tidak boleh lebih kecil dari pembacaan sebelumnya"
}
```

#### 400 - Image Upload Error
```json
{
  "status": "error",
  "message": "Gagal mengupload foto meter"
}
```

#### 500 - Server Error
```json
{
  "status": "error",
  "message": "Terjadi kesalahan saat menyimpan data meter reading: [detail error]"
}
```

## Example Usage

### 1. Submit Meter Reading (JSON - Tanpa Foto)
```bash
curl -X POST "http://localhost:8000/api/v1/store-meter-reading" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "customer_id": 114,
    "registered_month_id": 1,
    "current_reading": 500.5,
    "notes": "Pembacaan normal",
    "reading_at": "2024-10-14"
  }'
```

**Response:**
```json
{
  "status": "success",
  "message": "Meter reading berhasil disimpan",
  "data": {
    "id": 6209,
    "current_reading": "500.50",
    "volume_usage": "84.28",
    "photo_url": null,
    "reading_at": "2024-10-14"
  }
}
```

### 2. Submit Meter Reading (Form Data - Dengan Foto)
```bash
curl -X POST "http://localhost:8000/api/v1/store-meter-reading" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json" \
  -F "customer_id=138" \
  -F "registered_month_id=1" \
  -F "current_reading=600.75" \
  -F "notes=Pembacaan dengan foto bukti" \
  -F "reading_at=2024-10-14" \
  -F "photo=@/path/to/meter_photo.jpg"
```

**Response:**
```json
{
  "status": "success",
  "message": "Meter reading berhasil disimpan",
  "data": {
    "id": 6210,
    "current_reading": "600.75",
    "volume_usage": "110.45",
    "photo_url": "/storage/meter_readings/2025/10/meter_reading_138_1760434957_QI6oRJiigH.png",
    "reading_at": "2024-10-14"
  }
}
```

### 3. Access Uploaded Image
```bash
# Image dapat diakses melalui URL publik
curl "http://localhost:8000/storage/meter_readings/2025/10/meter_reading_138_1760434957_QI6oRJiigH.png"
```

## Flutter Integration

### Dart HTTP Request (Tanpa Image)
```dart
import 'dart:convert';
import 'package:http/http.dart' as http;

Future<Map<String, dynamic>> submitMeterReading({
  required String token,
  required int customerId,
  required int registeredMonthId,
  required double currentReading,
  String? notes,
  required String readingAt,
}) async {
  final response = await http.post(
    Uri.parse('http://localhost:8000/api/v1/store-meter-reading'),
    headers: {
      'Authorization': 'Bearer $token',
      'Accept': 'application/json',
      'Content-Type': 'application/json',
    },
    body: jsonEncode({
      'customer_id': customerId,
      'registered_month_id': registeredMonthId,
      'current_reading': currentReading,
      'notes': notes,
      'reading_at': readingAt,
    }),
  );

  return jsonDecode(response.body);
}
```

### Dart HTTP Request (Dengan Image)
```dart
import 'dart:io';
import 'package:http/http.dart' as http;

Future<Map<String, dynamic>> submitMeterReadingWithPhoto({
  required String token,
  required int customerId,
  required int registeredMonthId,
  required double currentReading,
  String? notes,
  required String readingAt,
  required File photoFile,
}) async {
  var request = http.MultipartRequest(
    'POST',
    Uri.parse('http://localhost:8000/api/v1/store-meter-reading'),
  );

  // Headers
  request.headers['Authorization'] = 'Bearer $token';
  request.headers['Accept'] = 'application/json';

  // Fields
  request.fields['customer_id'] = customerId.toString();
  request.fields['registered_month_id'] = registeredMonthId.toString();
  request.fields['current_reading'] = currentReading.toString();
  request.fields['reading_at'] = readingAt;
  if (notes != null) request.fields['notes'] = notes;

  // File
  request.files.add(await http.MultipartFile.fromPath('photo', photoFile.path));

  final streamedResponse = await request.send();
  final response = await http.Response.fromStream(streamedResponse);

  return jsonDecode(response.body);
}
```

### Flutter Camera Integration Example
```dart
import 'package:camera/camera.dart';
import 'package:image_picker/image_picker.dart';

class MeterReadingSubmission {
  final ImagePicker _picker = ImagePicker();

  Future<void> takeMeterPhoto() async {
    try {
      final XFile? photo = await _picker.pickImage(
        source: ImageSource.camera,
        maxWidth: 1024,
        maxHeight: 1024,
        imageQuality: 80,
      );

      if (photo != null) {
        // Submit with photo
        final result = await submitMeterReadingWithPhoto(
          token: userToken,
          customerId: currentCustomerId,
          registeredMonthId: currentMonthId,
          currentReading: meterReadingValue,
          notes: notesController.text,
          readingAt: DateTime.now().toString().split(' ')[0],
          photoFile: File(photo.path),
        );

        if (result['status'] == 'success') {
          // Success handling
          print('Photo URL: ${result['data']['photo_url']}');
        }
      }
    } catch (e) {
      print('Error taking photo: $e');
    }
  }
}
```

## File Storage Details

### Directory Structure
```
storage/app/public_html/meter_readings/
├── 2024/
│   ├── 10/
│   │   ├── meter_reading_138_1760434957_QI6oRJiigH.png
│   │   └── meter_reading_114_1760434666_lF1Fl2uGod.jpg
│   └── 11/
└── 2025/
    └── 10/
        └── meter_reading_138_1760434957_QI6oRJiigH.png
```

### Public Access
- **Symbolic Link**: `public/storage` → `storage/app/public`
- **URL Pattern**: `{APP_URL}/storage/meter_readings/{YYYY}/{MM}/{filename}`
- **Example**: `http://localhost:8000/storage/meter_readings/2025/10/meter_reading_138_1760434957_QI6oRJiigH.png`

## Database Schema
Data disimpan di tabel `meter_readings` dengan struktur:
```sql
- id (primary key)
- pam_id (foreign key to pams)
- meter_id (foreign key to meters)
- registered_month_id (foreign key to registered_months)
- previous_reading (decimal)
- current_reading (decimal)
- volume_usage (decimal, calculated)
- notes (text, nullable)
- photo_url (string, nullable)
- reading_by (foreign key to users)
- reading_at (date)
- status (enum: pending, approved, rejected)
- created_at
- updated_at
```

## Security Considerations

1. **Authentication**: Bearer token required
2. **File Type Validation**: Only JPEG, JPG, PNG allowed
3. **File Size Limit**: Maximum 5MB
4. **PAM Isolation**: User hanya bisa input untuk customer dalam PAM-nya
5. **Unique Filenames**: Prevent file conflicts dengan timestamp + random string

## Performance Tips

1. **Image Compression**: Compress images on mobile before upload
2. **Progressive Upload**: Show progress indicator untuk user experience
3. **Offline Queue**: Store submissions locally jika koneksi buruk
4. **Batch Upload**: Consider batch upload untuk multiple readings

## Testing

### Test Scenarios
1. ✅ Submit tanpa foto (JSON) → Success
2. ✅ Submit dengan foto (Form Data) → Success
3. ✅ File upload dan akses URL → Success
4. 🧪 Current reading < previous reading → Validation error
5. 🧪 Invalid image format → Upload error
6. 🧪 File size > 5MB → Upload error
7. 🧪 Customer dari PAM lain → Security error

### Sample Test Data
- Customer ID 114, 138: Valid customers di PAM 1
- Registered Month ID 1: Valid month
- Image: `/tmp/test_meter_image.png` (test file)

API sudah production-ready untuk integrasi Flutter! 🚀