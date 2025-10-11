# Database Migration dan Model Setup

## Urutan Eksekusi Migration

Untuk menjalankan migration tanpa error, ikuti urutan berikut:

```bash
# 1. Jalankan migration default Laravel terlebih dahulu
php artisan migrate

# 2. Jalankan migration baru sesuai urutan
php artisan migrate --path=database/migrations/2024_10_10_000001_modify_users_table.php
php artisan migrate --path=database/migrations/2024_10_10_000002_create_pams_table.php
php artisan migrate --path=database/migrations/2024_10_10_000003_create_areas_table.php
php artisan migrate --path=database/migrations/2024_10_10_000004_create_tariff_groups_table.php
php artisan migrate --path=database/migrations/2024_10_10_000005_create_tariff_tiers_table.php
php artisan migrate --path=database/migrations/2024_10_10_000006_create_fixed_fees_table.php
php artisan migrate --path=database/migrations/2024_10_10_000007_create_customers_table.php
php artisan migrate --path=database/migrations/2024_10_10_000008_create_meters_table.php
php artisan migrate --path=database/migrations/2024_10_10_000009_create_meter_records_table.php
php artisan migrate --path=database/migrations/2024_10_10_000010_create_bills_table.php
php artisan migrate --path=database/migrations/2024_10_10_000011_create_monthly_reports_table.php
php artisan migrate --path=database/migrations/2024_10_10_000012_create_activity_logs_table.php
php artisan migrate --path=database/migrations/2024_10_10_000013_add_foreign_key_to_users_table.php
```

## Atau jalankan semua sekaligus:
```bash
php artisan migrate
```

## Struktur Database

### Tabel Utama:
1. **users** - Pengguna sistem (petugas PAM)
2. **pams** - Data Perusahaan Air Minum
3. **areas** - Wilayah/Area dalam PAM
4. **tariff_groups** - Grup Tarif
5. **tariff_tiers** - Tingkatan Tarif berdasarkan volume
6. **fixed_fees** - Biaya tetap (admin, pemeliharaan, dll)
7. **customers** - Data Pelanggan
8. **meters** - Data Meter Air
9. **meter_records** - Pencatatan Pembacaan Meter
10. **bills** - Tagihan
11. **monthly_reports** - Laporan Bulanan
12. **activity_logs** - Log Aktivitas Sistem

### Relasi Antar Tabel:
- PAM → Areas (1:N)
- PAM → TariffGroups (1:N)
- PAM → Customers (1:N)
- PAM → Users (1:N)
- Area → Customers (1:N)
- TariffGroup → Customers (1:N)
- TariffGroup → TariffTiers (1:N)
- TariffGroup → FixedFees (1:N)
- Customer → Meters (1:N)
- Meter → MeterRecords (1:N)
- MeterRecord → Bills (1:N)
- User → MeterRecords (recorded_by)
- User → MonthlyReports (generated_by)

## Model yang Telah Dibuat:

### 1. Pam Model
- Manages: areas, customers, tariff groups, users, activity logs
- Features: coordinate storage as JSON

### 2. Area Model
- Belongs to: PAM
- Has many: customers

### 3. TariffGroup Model
- Belongs to: PAM
- Has many: customers, tariff tiers, fixed fees

### 4. TariffTier Model
- Belongs to: PAM, TariffGroup
- Features: meter range and pricing

### 5. FixedFee Model
- Belongs to: PAM, TariffGroup
- Features: fixed monthly charges

### 6. Customer Model
- Belongs to: PAM, Area, TariffGroup
- Has many: meters, bills

### 7. Meter Model
- Belongs to: PAM, Customer
- Has many: meter records
- Features: installation tracking

### 8. MeterRecord Model
- Belongs to: PAM, Meter, User (recorded_by)
- Has many: bills
- Features: period-based readings

### 9. Bill Model
- Belongs to: PAM, Customer, MeterRecord
- Features: billing and payment tracking

### 10. MonthlyReport Model
- Belongs to: PAM, User (generated_by)
- Features: monthly aggregated data

### 11. ActivityLog Model
- Belongs to: PAM, User
- Features: system audit trail with old/new values

### 12. User Model (Modified)
- Belongs to: PAM
- Has many: recorded meter records, generated reports, activity logs
- Added: pam_id, phone fields

## Catatan Penting:

1. **Foreign Key Constraints**: Semua relasi menggunakan cascade delete
2. **Indexing**: Ditambahkan index pada kolom yang sering digunakan untuk query
3. **Unique Constraints**: 
   - PAM code
   - Meter serial number
   - Customer number per PAM
   - Bill number per PAM
4. **Data Types**: 
   - Decimal untuk nilai mata uang dan volume
   - JSON untuk coordinate dan activity log values
   - Enum untuk status fields
5. **Timestamps**: Semua tabel menggunakan Laravel timestamps

## Testing Migration:

```bash
# Test rollback
php artisan migrate:rollback

# Test fresh migration
php artisan migrate:fresh

# Generate sample data
php artisan db:seed
```

## Seeder yang Perlu Dibuat:
1. PamSeeder
2. AreaSeeder  
3. TariffGroupSeeder
4. TariffTierSeeder
5. FixedFeeSeeder
6. CustomerSeeder
7. MeterSeeder