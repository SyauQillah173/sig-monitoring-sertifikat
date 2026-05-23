# Arsitektur Fondasi Proyek

## Tujuan

Menyediakan fondasi aplikasi yang rapi, mudah dikembangkan, dan cocok untuk proyek skripsi monitoring sertifikat produk berbasis web.

## Arsitektur yang Dipilih

- `Laravel 13` sebagai backend utama
- `MySQL` sebagai database produksi dan lokal utama
- `Livewire + Blade` agar frontend tetap sederhana dan fokus fungsional
- `Fortify` untuk autentikasi
- `Spatie Laravel Permission` untuk role dan permission

## Alasan Pemilihan

- Cepat untuk membangun sistem informasi internal.
- Struktur bawaan Laravel rapi dan mudah diuji.
- Livewire cocok untuk aplikasi CRUD dan dashboard tanpa kompleksitas SPA penuh.
- Permission-based authorization lebih fleksibel dibanding hanya mengecek role mentah.

## Role dan Arah Hak Akses

### Admin

- Kelola user dan role
- Kelola master data
- Kelola sertifikat
- Lihat laporan

### Petugas

- Lihat dashboard
- Lihat master data
- Input dan update sertifikat

## Permission Awal

- `dashboard.view`
- `users.manage`
- `master-data.view`
- `master-data.manage`
- `certificates.view`
- `certificates.manage`
- `reports.view`

## Struktur Folder yang Dipakai

- `app/Enums`
  Menyimpan enum `UserRole` dan `AppPermission`.
- `app/Models`
  Model utama aplikasi.
- `app/Providers`
  Pengaturan gate, middleware alias, dan konfigurasi auth.
- `database/migrations`
  Skema tabel aplikasi dan tabel permission.
- `database/seeders`
  Seeder role, permission, dan akun awal.
- `resources/views`
  Blade view untuk landing page, dashboard, dan halaman auth.
- `routes/web.php`
  Route web dasar.

## Daftar Fitur Inti

1. Login/logout
2. Dashboard berdasarkan role
3. Manajemen user dan role
4. Master data produk
5. Master jenis sertifikat
6. Data sertifikat produk
7. Upload dokumen sertifikat
8. Monitoring masa berlaku
9. Laporan untuk admin
10. Evaluasi usability

## Urutan Implementasi yang Disarankan

### Fase 1

- Setup proyek
- Autentikasi
- Role dan permission
- Dashboard awal

### Fase 2

- Master data produk
- Master jenis sertifikat

### Fase 3

- CRUD sertifikat
- Upload file sertifikat
- Status aktif, akan habis, kedaluwarsa

### Fase 4

- Filter monitoring
- Ringkasan dashboard
- Laporan admin

### Fase 5

- Pengujian usability
- Penyempurnaan UI
- Hardening validasi dan otorisasi

## Catatan Pengembangan

- Untuk tahap awal, registrasi publik dinonaktifkan.
- Pembuatan user dilakukan oleh admin.
- Gunakan `FormRequest`, `Policy`, dan `Service` saat modul mulai bertambah agar controller tetap tipis.
- Setelah modul data sertifikat mulai dibuat, siapkan folder `app/Services` dan `app/Policies` untuk menjaga struktur tetap rapi.
