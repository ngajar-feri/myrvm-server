# Backup Project: MyRVM-Server
Timestamp: 17012026-1000
Code: [TR1]

```
  __  __       _     ____  __      __  __  __
 |  \/  |     | |   |  _ \ \ \    / / |  \/  |
 | \  / |_   _| |   | |_) | \ \  / /  | \  / |
 | |\/| | | | | |   |  _ <   \ \/ /   | |\/| |
 | |  | | |_| | |___| |_) |   \  /    | |  | |
 |_|  |_|\__, |_____|____/     \/     |_|  |_|
          __/ |
         |___/      SERVER BACKEND
```

## Deskripsi
Backup ini berisi source code lengkap dari aplikasi backend **MyRVM-Server**, yang berfungsi sebagai pusat orkestrasi (The Hub & Brain) untuk ekosistem Reverse Vending Machine.
Sistem ini menangani manajemen pengguna, pencatatan transaksi, sistem wallet/voucher, telemetri real-time, dan versioning model AI.

## Tech Stack Utama
- **Framework**: Laravel 10 (PHP 8.2+)
- **Database**: PostgreSQL / MySQL
- **Cache & Queue**: Redis
- **Authentication**: Laravel Sanctum (Token-based Auth)
- **API Documentation**: L5-Swagger (OpenAPI 3.0)
- **Frontend (Dashboard)**: Vue.js 3 + Inertia.js
- **Real-time**: Laravel Reverb (WebSocket)
- **Storage**: MinIO (S3 Compatible)

## Fitur Utama
1.  **Role-Based Access Control (RBAC)**: Manajemen hak akses untuk Super Admin, Admin, Technician, Tenant, dan User.
2.  **API Standard**: RESTful API dengan dokumentasi Swagger lengkap (termasuk kode status HTTP).
3.  **Telemetry Handling**: Menerima data sensor dari Edge Devices (RVM-Edge).
4.  **Transaction Logging**: Pencatatan aktivitas penukaran botol/kaleng.
5.  **Voucher System**: Generate dan redeem voucher untuk user.

## Struktur Direktori & File Referensi

Berikut adalah struktur folder utama dan fungsinya dalam backup ini:

- **`app/`**: Inti logika aplikasi.
    - `Http/Controllers/`: Menangani request API dan Web (contoh: `AuthController`, `RvmMachineController`).
    - `Http/Middleware/`: Filter request (contoh: `ApiLogger` untuk mencatat log aktivitas).
    - `Models/`: Representasi data database (contoh: `User`, `Transaction`, `RvmMachine`).
- **`config/`**: File konfigurasi sistem.
    - `l5-swagger.php`: Konfigurasi dokumentasi API.
    - `sanctum.php`: Konfigurasi autentikasi token.
    - `cors.php`, `database.php`, dll.
- **`database/`**: Migrasi database dan seeder.
    - `migrations/`: Skema tabel database.
    - `seeders/`: Data awal untuk testing (Users, Roles).
- **`public/`**: Aset publik yang dapat diakses browser.
    - `vendor/`: Template admin dashboard (HTML/CSS/JS).
    - `css/`, `js/`: Aset kompilasi frontend.
- **`resources/`**: View dan raw assets.
    - `views/vendor/l5-swagger/`: Kustomisasi tampilan Swagger UI (`index.blade.php` dengan auto-auth).
- **`routes/`**: Definisi URL.
    - `api.php`: Endpoint API (dilindungi Sanctum).
    - `web.php`: Rute halaman web/dashboard.
- **`storage/`**: File yang digenerate aplikasi.
    - `api-docs/`: File JSON hasil generate Swagger (`api-docs.json`).
    - `logs/`: Log aplikasi Laravel.
- **`tests/`**: Unit dan Feature testing.
- **Root Files**:
    - `.env.example`: Template variabel lingkungan.
    - `composer.json`: Dependensi PHP.
    - `package.json`: Dependensi JavaScript/Node.js.
    - `README.md`: Dokumentasi utama project.
    - `README_BACKUP.md`: File ini (informasi backup).

## Catatan Penting untuk Restore
1.  **Environment**: Copy `.env.example` ke `.env` dan sesuaikan konfigurasi database/redis.
2.  **Dependencies**: Jalankan `composer install` dan `npm install`.
3.  **Database**: Jalankan `php artisan migrate --seed` untuk membuat tabel dan data awal.
4.  **Swagger**: Jalankan `php artisan l5-swagger:generate` untuk memperbarui dokumentasi API.
5.  **Key**: Jalankan `php artisan key:generate`.

## Log Perubahan Terakhir
- **Swagger UI**: Ditambahkan `responseInterceptor` untuk otomatis mengisi token Bearer setelah login.
- **API Response**: Semua endpoint kini memiliki dokumentasi kode status lengkap (200, 201, 302, 400, 401, 403, 404, 422, 500).
- **Backup Strategy**: Folder dikompresi (bukan dicopy) untuk efisiensi penyimpanan.

---
*Dibuat otomatis oleh AI Assistant [TR1]*
