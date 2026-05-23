# SIG Monitoring Sertifikat Produk

Fondasi proyek Laravel untuk skripsi:

> Pengembangan Sistem Monitoring Sertifikat Produk Berbasis Web dengan Evaluasi Usability

Stack utama:

- Laravel 13
- Livewire starter kit
- MySQL
- Laravel Fortify untuk login
- Spatie Laravel Permission untuk role dan permission

## Fitur Fondasi

- Login/logout siap pakai
- Role pengguna: `admin`, `petugas`
- Seeder role, permission, dan akun awal
- Dashboard awal yang sudah menampilkan konteks role
- Struktur proyek siap dilanjutkan ke modul inti

## Akun Awal

Setelah menjalankan seeder, akun berikut akan tersedia:

- `admin@sig.test`
- `petugas@sig.test`
Password default membaca dari `.env`:

```env
SEED_USER_PASSWORD=Password123!
```

Ganti password ini setelah login pertama jika dipakai di luar lingkungan lokal.

## Setup Awal

1. Pastikan `PHP`, `Composer`, `Node.js`, dan `MySQL` aktif.
2. Buat database MySQL bernama `monitoring_sertifikat`.
3. Sesuaikan file `.env` jika username/password MySQL berbeda.
4. Jalankan perintah berikut:

```bash
composer install
npm install
php artisan key:generate
php artisan migrate --seed
npm run build
composer run dev
```

Jika MySQL belum aktif dari XAMPP, nyalakan dulu service `MySQL` sebelum `migrate --seed`.

## Struktur Folder Penting

- `app/Enums` untuk enum role dan permission
- `app/Models` untuk model Eloquent
- `app/Providers` untuk konfigurasi aplikasi, gate, dan Fortify
- `database/migrations` untuk skema database
- `database/seeders` untuk role, permission, dan user awal
- `resources/views` untuk tampilan Blade dan halaman Livewire
- `routes/web.php` untuk route web utama
- `docs/project-foundation.md` untuk arsitektur dan roadmap

## Dependensi Penting

- `laravel/framework`
- `laravel/fortify`
- `livewire/livewire`
- `livewire/flux`
- `spatie/laravel-permission`

## Catatan Arsitektur

- Registrasi publik dimatikan karena aplikasi ini ditujukan untuk penggunaan internal.
- Otorisasi menggunakan permission agar fleksibel saat modul bertambah.
- Admin diperlakukan sebagai super-admin melalui `Gate::before`.
- Route middleware alias `role`, `permission`, dan `role_or_permission` sudah disiapkan untuk modul berikutnya.

## Tahap Implementasi Berikutnya

1. Master data produk
2. Master jenis sertifikat
3. CRUD sertifikat produk
4. Upload dokumen sertifikat
5. Monitoring masa berlaku dan notifikasi
6. Dashboard laporan admin
7. Evaluasi usability
