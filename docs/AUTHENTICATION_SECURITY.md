# Sistem Autentikasi & Keamanan Login

## Overview

Sistem ini menggunakan kebijakan keamanan yang ketat dengan hanya memperbolehkan pengguna dengan role **Super Administrator** untuk mengakses seluruh sistem.

## 🛡️ Kebijakan Akses

### Role yang Diperbolehkan
- **Super Administrator (superadmin)**: Akses penuh ke seluruh sistem

### Role yang Ditolak
- **Role lain apapun**: Akses ditolak pada tahap login
- **User tanpa role**: Akses ditolak pada tahap login

## 🔐 Implementasi Keamanan

### 1. Login Controller (`AuthController.php`)

**Validasi Login:**
- Validasi email dan password
- Pengecekan role setelah kredensial valid
- Auto-logout jika role tidak sesuai

**Logging:**
- Login berhasil: User info, IP, user agent
- Login gagal: Email, IP, user agent, reason
- Akses ditolak: User info, route, IP, reason

### 2. Middleware SuperAdminOnly

**Fungsi:**
- Cek user authentication
- Validasi role superadmin pada setiap request
- Auto-logout dan redirect jika tidak superadmin

**Route Protection:**
- Semua route dalam group `role:superadmin`
- Middleware tambahan untuk keamanan ekstra

### 3. Login View (`login.blade.php`)

**Informasi User:**
- Kebijakan akses jelas ditampilkan
- Pesan error yang informatif
- Langkah selanjutnya untuk user non-superadmin

**Styling:**
- Alert warning untuk error akses role
- Alert danger untuk error kredensial
- Alert info untuk kebijakan akses

## 🚨 Alur Autentikasi

### Login Berhasil (Super Admin)
1. User memasukkan email dan password
2. Sistem validasi kredensial
3. Sistem cek role (harus 'superadmin')
4. ✅ Login berhasil → Dashboard

### Login Ditolak (Non-Super Admin)
1. User memasukkan email dan password valid
2. Sistem validasi kredensial (berhasil)
3. Sistem cek role (bukan 'superadmin')
4. ❌ Auto-logout → Halaman login dengan error

### Login Gagal (Kredensial Salah)
1. User memasukkan email/password salah
2. ❌ Validasi gagal → Halaman login dengan error

## 📝 Error Messages

### Akses Role Error
```
- "Akses ditolak. Hanya pengguna dengan role Super Admin yang dapat mengakses sistem ini."
- "Anda tidak memiliki izin akses yang cukup. Hubungi administrator sistem."
```

### Kredensial Error
```
- "Email atau password salah."
- "Sistem ini hanya dapat diakses oleh Super Administrator."
```

### Unauthorized Access Error
```
- "Akses ditolak. Hanya Super Administrator yang dapat mengakses halaman ini."
- "Anda tidak memiliki izin akses yang cukup. Hubungi administrator sistem."
```

## 🔍 Logging & Monitoring

### Types of Logs

#### Info Logs
- Login successful (Super Admin)
- Logout

#### Warning Logs
- Failed login attempts
- Unauthorized access attempts
- Non-superadmin login attempts

### Log Structure
```json
{
  "user_id": 123,
  "email": "admin@example.com",
  "name": "Admin User",
  "roles": ["superadmin"],
  "route": "/dashboard",
  "ip": "192.168.1.100",
  "user_agent": "Mozilla/5.0...",
  "timestamp": "2025-11-06T10:30:00Z"
}
```

## 🛠️ Helper Classes & Services

### AuthService (`app/Services/AuthService.php`)
```php
// Check superadmin status
AuthService::isSuperAdmin()

// Log login activity
AuthService::logLogin('Superadmin login successful');

// Log failed attempts
AuthService::logFailedLogin('user@example.com', 'Invalid role');

// Force logout
AuthService::forceLogout('Security policy violation');
```

### SuperAdminOnly Middleware
```php
// Applied to routes
Route::middleware('superadmin.only')->group(function () {
    // Protected routes
});
```

## 🚨 Security Features

### 1. Role-Based Access Control (RBAC)
- Hanya superadmin yang bisa login
- Middleware double-check pada setiap request
- Auto-logout untuk akses tidak sah

### 2. Session Management
- Session regeneration pada login
- Session invalidate pada logout
- CSRF protection pada semua forms

### 3. Logging & Auditing
- Semua aktivitas login tercatat
- Akses tidak sah termonitoring
- IP address dan user agent logging

### 4. Error Handling
- Pesan error yang informatif
- Pengalihan ke login page
- Clear error messages untuk user

## 📱 User Experience

### For Super Admins
- Login langsung ke dashboard
- Akses penuh ke semua fitur
- Session yang aman dan persistent

### For Non-Super Admins
- Pesan error yang jelas
- Langkah selanjutnya yang informatif
- Tidak ada akses ke sistem

## 🔧 Configuration

### Required Packages
- `spatie/laravel-permission` untuk role management
- Laravel's built-in authentication

### Database Setup
1. Tabel `users` sudah ada
2. Tabel `roles` dan `permissions` (Spatie)
3. Tabel `model_has_roles` dan `model_has_permissions`

### Role Assignment
```php
$user = User::find(1);
$user->assignRole('superadmin');
```

## 🚨 Best Practices

### For Administrators
1. Gunakan password yang kuat
2. Monitor logs secara berkala
3. Laporkan aktivitas mencurigakan
4. Update password secara berkala

### For Developers
1. Gunakan AuthService untuk logging
2. Selalu cek role sebelum mengizinkan akses
3. Implement middleware untuk keamanan
4. Log semua aktivitas penting

## 🚨 Emergency Procedures

### Jika Ada User Non-Superadmin yang Login
1. Cek logs untuk identifikasi user
2. Force logout user lewat database
3. Update role user jika diperlukan
4. Monitor aktivitas selanjutnya

### Jika Ada Akses Tidak Sah
1. Cek logs untuk IP dan user agent
2. Blok IP jika ada percobaan brute force
3. Review kebijakan akses user
4. Update security settings

---

**Sistem ini dirancang dengan prioritas keamanan tinggi untuk melindungi data sensitif dan memastikan hanya authorized personnel yang dapat mengakses sistem.**