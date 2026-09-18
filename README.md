# NexaAdmin Dashboard

Template dashboard berbasis Laravel dengan AdminLTE, Bootstrap, MySQL, autentikasi lengkap, RBAC, notifikasi, audit log, dan pengaturan aplikasi.

## Teknologi

- Laravel 13
- PHP 8.3+
- MySQL 8+ / MariaDB
- Bootstrap 5
- AdminLTE 4
- Font Awesome 6
- DataTables Bootstrap 5
- SweetAlert2
- Google OAuth 2.0

## Fitur

### Dashboard dan UI

- Dashboard statistik, grafik performa, aktivitas terbaru, dan pesanan
- Layout AdminLTE responsive untuk desktop dan mobile
- Dark mode / light mode dengan penyimpanan preferensi browser
- Pengaturan warna utama, sidebar, navbar, footer, dan tema default
- Footer dan versi aplikasi dinamis
- DataTables dengan pencarian, sorting, pagination, jumlah baris, dan bahasa Indonesia

### Autentikasi

- Login dan logout
- Registrasi pengguna
- Reset password melalui email
- Change password
- Profile pengguna bergaya Curriculum Vitae
- Upload avatar
- Session management
- Penghentian sesi tertentu atau seluruh sesi perangkat lain
- Rate limiting login: maksimal 5 percobaan per menit berdasarkan email dan IP
- Login dan registrasi dengan akun Google OAuth 2.0

### Role Based Access Control

Role bawaan:

- `admin`: akses penuh dan pengaturan sistem
- `manager`: role operasional
- `user`: akses dasar aplikasi

Admin dapat:

- Menambah pengguna
- Mengedit pengguna
- Mengubah role
- Menghapus pengguna
- Mencari dan memfilter pengguna

### Notifikasi

- Notifikasi database
- Dropdown notifikasi di navbar
- Badge jumlah notifikasi unread
- Halaman semua notifikasi
- Tandai satu notifikasi sebagai dibaca
- Tandai semua notifikasi sebagai dibaca
- Toast berhasil, gagal, warning, dan informasi menggunakan SweetAlert2
- Konfirmasi sebelum logout, menghapus pengguna, dan menghentikan sesi

### Audit Log

Audit log mencatat:

- Aktor/user
- Aksi
- Deskripsi perubahan
- Nilai lama dan nilai baru
- IP address
- User agent
- Waktu aktivitas

Aktivitas yang dicatat mencakup login, logout, registrasi, perubahan profil, password, avatar, role, pengguna, pengaturan aplikasi, notifikasi, session, dan Google OAuth.

## Kebutuhan Sistem

- PHP 8.3 atau lebih baru
- Composer
- MySQL 8+ atau MariaDB
- Apache/Nginx atau `php artisan serve`
- Ekstensi PHP: `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `json`, `curl`

## Instalasi

Masuk ke folder project:

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/template_dashboard
```

Install dependency:

```bash
composer install
```

Salin environment file jika belum tersedia:

```bash
cp .env.example .env
php artisan key:generate
```

## Konfigurasi MySQL

Buat database:

```sql
CREATE DATABASE laravel CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Atur `.env`:

```env
APP_NAME=NexaAdmin
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=root
DB_PASSWORD=

SESSION_DRIVER=database
CACHE_STORE=database
FILESYSTEM_DISK=local
MAIL_MAILER=log
```

Jika menggunakan XAMPP Apache secara langsung, gunakan `APP_URL` sesuai URL aplikasi, misalnya:

```env
APP_URL=http://localhost/template_dashboard/public
```

Jalankan migration dan seed:

```bash
php artisan migrate --seed
php artisan storage:link
```

> `php artisan migrate:fresh --seed` akan menghapus seluruh tabel dan data database. Gunakan hanya pada database development.

## Akun Demo

```text
Email    : admin@example.com
Password : password
Role     : admin
```

## Menjalankan Aplikasi

Dengan Laravel development server:

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

Buka:

```text
http://localhost:8000/login
```

## Google OAuth

Login Google menggunakan Google OAuth 2.0 langsung melalui HTTP Laravel, tanpa dependency Socialite.

1. Buka Google Cloud Console.
2. Buat project atau gunakan project yang sudah ada.
3. Aktifkan Google Identity / OAuth consent screen.
4. Buat OAuth Client ID dengan tipe **Web application**.
5. Tambahkan Authorized redirect URI yang sama persis dengan URL aplikasi.

Untuk `php artisan serve`:

```text
http://localhost:8000/auth/google/callback
```

Untuk XAMPP Apache:

```text
http://localhost/template_dashboard/public/auth/google/callback
```

Masukkan kredensial melalui menu admin:

```text
/admin/settings
```

Field yang tersedia:

- Google Client ID
- Google Client Secret
- Google API Key
- Google Redirect URI

Client Secret dan API Key disimpan terenkripsi menggunakan Laravel Crypt. Jika Redirect URI dikosongkan, sistem menggunakan URL callback berdasarkan `APP_URL`.

## Email Reset Password

Default menggunakan mail log untuk development:

```env
MAIL_MAILER=log
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"
```

Link reset password dapat diperiksa di:

```text
storage/logs/laravel.log
```

Untuk production, ganti konfigurasi mail dengan SMTP provider yang digunakan.

## Menu Utama

### User

- `/dashboard` — dashboard utama
- `/profile` — profil CV, password, dan sesi aktif
- `/notifications` — daftar notifikasi
- `/change-password` — halaman change password legacy
- `/sessions` — halaman session management legacy

### Admin

- `/admin/users` — management pengguna
- `/admin/settings` — pengaturan aplikasi, warna, tema, dan Google OAuth
- `/admin/audit-logs` — audit log aktivitas aplikasi

Route admin dilindungi middleware:

```php
Route::middleware('role:admin')->group(function () {
    // route khusus admin
});
```

## Struktur Penting

```text
app/
├── Http/Controllers/
│   ├── AccountController.php
│   ├── AdminUserController.php
│   ├── AuditLogController.php
│   ├── AuthController.php
│   ├── GoogleAuthController.php
│   ├── NotificationController.php
│   └── SettingController.php
├── Http/Middleware/RoleMiddleware.php
├── Models/
│   ├── AuditLog.php
│   ├── Setting.php
│   └── User.php
├── Notifications/SystemNotification.php
└── Support/AuditLogger.php
resources/views/
├── account/
├── admin/
├── auth/
├── layouts/admin.blade.php
└── notifications/
```

## Validasi Setelah Setup

```bash
php artisan about
php artisan migrate:status
php artisan route:list
php artisan view:cache
php artisan test
```

Bersihkan cache konfigurasi jika mengubah `.env`:

```bash
php artisan optimize:clear
```

## Lisensi

Project ini menggunakan Laravel dan package open-source dengan lisensi masing-masing. Kode aplikasi dapat dikembangkan sesuai kebutuhan project.
