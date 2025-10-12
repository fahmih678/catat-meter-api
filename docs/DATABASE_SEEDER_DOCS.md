# DATABASE SEEDER DOCUMENTATION

**Tanggal:** 11 Oktober 2025  
**Status:** ✅ **SEEDER LENGKAP UNTUK TESTING**

## 🌟 **OVERVIEW SEEDERS**

Seeder ini dirancang untuk membuat data testing yang realistis untuk semua modul dalam sistem PAM (Perusahaan Air Minum). Data yang dihasilkan menggunakan konteks Indonesia dengan nama, alamat, dan nomor telepon yang sesuai.

---

## 📋 **DAFTAR SEEDERS**

### **1. PamSeeder**
**File:** `database/seeders/PamSeeder.php`

**Data yang dibuat:**
- ✅ **8 PAM** di berbagai wilayah Jakarta dan sekitarnya
- ✅ **7 PAM aktif** + **1 PAM non-aktif** untuk testing
- ✅ **Koordinat geografis** yang akurat
- ✅ **Kode PAM unik** untuk setiap wilayah

**Contoh Data:**
```php
[
    'name' => 'PAM Jakarta Pusat',
    'code' => 'PAMJAKPUR',
    'phone' => '021-5555-0001',
    'address' => 'Jl. Sudirman No. 123, Jakarta Pusat, DKI Jakarta 10110',
    'status' => 'active',
    'coordinate' => '{"lat": -6.2088, "lng": 106.8456}'
]
```

---

### **2. AreaSeeder**
**File:** `database/seeders/AreaSeeder.php`

**Data yang dibuat:**
- ✅ **5 zona per PAM** (40 zona total)
- ✅ **Zona A**: Perumahan Elite
- ✅ **Zona B**: Perumahan Menengah  
- ✅ **Zona C**: Pemukiman Padat
- ✅ **Zona D**: Industri
- ✅ **Zona E**: Komersial

**Format Kode:** `{PAM_CODE}-{ZONA_CODE}`  
**Contoh:** `PAMJAKPUR-ZNA`

---

### **3. TariffGroupSeeder**
**File:** `database/seeders/TariffGroupSeeder.php`

**Data yang dibuat:**
- ✅ **8 golongan tarif per PAM** (64 total)
- ✅ **Rumah Tangga**: Kecil, Sedang, Besar
- ✅ **Niaga**: Kecil, Menengah, Besar
- ✅ **Industri** & **Sosial**

**Klasifikasi Berdasarkan:**
- 🏠 **Rumah Tangga**: Berdasarkan volume pemakaian
- 🏪 **Niaga**: Berdasarkan skala usaha
- 🏭 **Industri**: Untuk keperluan pabrik
- 🏥 **Sosial**: Untuk fasilitas umum

---

### **4. TariffTierSeeder**
**File:** `database/seeders/TariffTierSeeder.php`

**Data yang dibuat:**
- ✅ **3-5 blok tarif bertingkat** per tariff group
- ✅ **Sistem tarif progresif** sesuai standar PDAM Indonesia
- ✅ **8 konfigurasi berbeda** untuk setiap jenis pelanggan
- ✅ **Tarif sosial** untuk golongan rumah tangga kecil

**Struktur Tarif Bertingkat:**

#### **🏠 Rumah Tangga Kecil:**
- **Blok I** (0-10 m³): Rp 1.500/m³ (Tarif sosial)
- **Blok II** (11-20 m³): Rp 2.500/m³
- **Blok III** (>20 m³): Rp 3.500/m³

#### **🏪 Niaga Menengah:**
- **Blok I** (0-30 m³): Rp 5.000/m³
- **Blok II** (31-100 m³): Rp 7.000/m³
- **Blok III** (>100 m³): Rp 9.000/m³

#### **🏭 Industri:**
- **Blok I** (0-100 m³): Rp 7.500/m³
- **Blok II** (101-500 m³): Rp 10.000/m³
- **Blok III** (>500 m³): Rp 12.500/m³

#### **🏥 Sosial:**
- **Blok I** (0-20 m³): Rp 1.000/m³ (Tarif khusus)
- **Blok II** (21-50 m³): Rp 2.000/m³
- **Blok III** (>50 m³): Rp 3.000/m³

**Fitur Khusus:**
- 🎯 **Progressive Pricing**: Semakin besar pemakaian, semakin mahal tarif
- 💰 **Subsidi Silang**: Tarif industri subsidikan tarif rumah tangga
- 📊 **Flexible Blocks**: Jumlah blok berbeda per kategori (3-5 blok)
- 🔄 **Future-proof**: Support effective date untuk perubahan tarif

---

### **5. FixedFeeSeeder**
**File:** `database/seeders/FixedFeeSeeder.php`

**Data yang dibuat:**
- ✅ **3 jenis biaya tetap** per tariff group
- ✅ **Biaya Beban**: Rp 15.000 - Rp 300.000
- ✅ **Biaya Administrasi**: Rp 5.000 (flat)
- ✅ **Biaya Meteran**: Rp 3.000 - Rp 20.000

**Struktur Biaya:**
```php
'Rumah Tangga Kecil' => [
    'Biaya Beban' => 15000,
    'Biaya Admin' => 5000,
    'Biaya Meteran' => 3000
],
'Industri' => [
    'Biaya Beban' => 300000,
    'Biaya Admin' => 5000,
    'Biaya Meteran' => 20000
]
```

---

### **6. CustomerSeeder**
**File:** `database/seeders/CustomerSeeder.php`

**Data yang dibuat:**
- ✅ **15-25 pelanggan per PAM** (~160 total)
- ✅ **Nama Indonesia** yang realistis
- ✅ **Alamat lengkap** dengan RT/RW
- ✅ **Nomor telepon** dengan format Indonesia
- ✅ **90% aktif**, 10% non-aktif

**Fitur Khusus:**
- 🇮🇩 **Locale Indonesia** dengan Faker
- 📱 **80% memiliki nomor telepon**
- 🏠 **Alamat sesuai zona area**
- 🎯 **Distribusi tarif yang realistis**

**Format Customer Number:** `{PAM_CODE}0001`

---

### **7. MeterSeeder**
**File:** `database/seeders/MeterSeeder.php`

**Data yang dibuat:**
- ✅ **85% pelanggan aktif** memiliki meter
- ✅ **7 brand meter** internasional
- ✅ **3 jenis meter**: mechanical, ultrasonic, electromagnetic
- ✅ **6 ukuran meter**: 15mm - 50mm
- ✅ **Serial number unik** per PAM

**Fitur Realistis:**
- 📅 **Tanggal instalasi**: 6 bulan - 5 tahun lalu
- 📊 **Previous reading**: 0-500 m³
- 📈 **Current reading**: Initial + pemakaian
- 🔧 **95% meter aktif**, 5% butuh perbaikan
- 📝 **Notes** untuk meter bermasalah

**Format Serial Number:** `{PAM_CODE}{YY}{XXXXXX}`

---

## 🚀 **CARA MENJALANKAN SEEDER**

### **1. Jalankan Semua Seeder:**
```bash
php artisan migrate:fresh --seed
```

### **2. Jalankan Seeder Spesifik:**
```bash
php artisan db:seed --class=PamSeeder
php artisan db:seed --class=CustomerSeeder
```

### **3. Hanya DatabaseSeeder:**
```bash
php artisan db:seed
```

---

## 📊 **STATISTIK DATA YANG DIHASILKAN**

| **Model** | **Jumlah** | **Keterangan** |
|-----------|------------|----------------|
| **PAM** | 8 | 7 aktif, 1 non-aktif |
| **Area** | 40 | 5 zona per PAM |
| **TariffGroup** | 64 | 8 golongan per PAM |
| **TariffTier** | ~256 | 3-5 blok per golongan |
| **FixedFee** | 192 | 3 biaya per golongan |
| **Customer** | ~160 | 15-25 per PAM |
| **Meter** | ~136 | 85% dari customer aktif |

---

## 🎯 **SKENARIO TESTING YANG DIDUKUNG**

### **✅ Modul PAM:**
- Testing PAM aktif vs non-aktif
- Search berdasarkan nama/kode
- Statistik per PAM

### **✅ Modul Customer:**
- Filter berdasarkan PAM/Area
- Customer dengan/tanpa meter
- Berbagai status customer

### **✅ Modul Meter:**
- Meter aktif vs rusak
- Berbagai brand dan ukuran
- History pembacaan meter

### **✅ Modul Tariff:**
- Berbagai golongan tarif
- **Tarif bertingkat progresif** (3-5 blok)
- Fixed fee per kategori
- Simulasi billing dengan tarif blok
- **Testing subsidi silang** antar golongan

### **✅ Testing Relasi:**
- Cascading delete
- Join query performance
- Foreign key constraints

---

## 🔗 **ENDPOINT TESTING YANG SIAP**

### **PAM Endpoints:**
```
GET /api/pams              → 8 PAMs
GET /api/pams/active       → 7 active PAMs
GET /api/pams/search?name=Jakarta → Filter PAMs
```

### **Customer Endpoints:**
```
GET /api/customers         → ~160 customers
GET /api/customers/pam/1   → Customers by PAM
GET /api/customers/area/1  → Customers by Area
```

### **Meter Endpoints:**
```
GET /api/meters            → ~136 meters
GET /api/meters/customer/1 → Meters by Customer
```

---

## 💡 **TIPS PENGGUNAAN**

### **1. Reset Data:**
```bash
php artisan migrate:fresh --seed
```

### **2. Tambah Data Khusus:**
```bash
php artisan tinker
>>> Customer::factory(10)->create(['pam_id' => 1])
```

### **3. Lihat Statistik:**
```bash
php artisan tinker
>>> DB::table('customers')->count()
>>> DB::table('meters')->where('status', 'active')->count()
```

---

## 🎉 **KESIMPULAN**

Seeder ini menyediakan **data testing yang komprehensif dan realistis** untuk:

✅ **API Development** - Testing semua endpoint dengan data nyata  
✅ **Frontend Development** - Data untuk UI/UX testing  
✅ **Performance Testing** - Volume data yang cukup untuk testing  
✅ **Business Logic Testing** - Skenario bisnis yang realistis  
✅ **Database Testing** - Relasi dan constraint testing  

**Ready for production testing!** 🚀