<x-layouts::app :title="'Pengaturan Sistem'">
    <div class="ui-page">
        <x-ui.page-header
            eyebrow="CMS Admin"
            title="Pengaturan Sistem"
            description="Pusat kontrol admin untuk user, tampilan publik, menu aplikasi, dan konfigurasi sistem."
        />

        @include('admin.master-data.partials.flash-messages')

        <section class="ui-form-panel">
            <div class="grid gap-4 p-6 md:grid-cols-2 xl:grid-cols-3">
                <div>
                    <h2 class="text-sm font-semibold text-slate-900">Fungsi CMS</h2>
                    <p class="mt-1 text-sm leading-6 text-slate-600">Area ini dipakai admin untuk mengatur bagian sistem yang boleh berubah tanpa edit kode: user, teks landing, menu sidebar, notifikasi, template, dan data master.</p>
                </div>
                <div>
                    <h2 class="text-sm font-semibold text-slate-900">Pengaturan Akun Tetap Pribadi</h2>
                    <p class="mt-1 text-sm leading-6 text-slate-600">Menu Pengaturan Akun hanya untuk profil dan keamanan akun yang sedang login. Semua pengaturan global dikelompokkan di Pengaturan Sistem.</p>
                </div>
                <div>
                    <h2 class="text-sm font-semibold text-slate-900">Akses Admin</h2>
                    <p class="mt-1 text-sm leading-6 text-slate-600">Halaman ini hanya bisa dibuka role Administrator. Petugas tetap memakai menu operasional yang sudah diizinkan saja.</p>
                </div>
            </div>
        </section>

        <section class="ui-cement-maintenance-grid">
            <a href="{{ route('admin.users.index') }}" class="ui-cement-maintenance-card">
                <span>Manajemen User</span>
                <strong>Akses</strong>
                <p class="mt-2 text-xs leading-5 text-slate-500">Tambah user, ubah role, kirim reset password, dan aktif/nonaktifkan akses login.</p>
            </a>
            <a href="{{ route('system-settings.public-appearance.edit') }}" class="ui-cement-maintenance-card">
                <span>Tampilan Publik</span>
                <strong>Landing</strong>
                <p class="mt-2 text-xs leading-5 text-slate-500">Ubah nama aplikasi, badge, judul hero, deskripsi, value point, dan footer halaman sebelum login.</p>
            </a>
            <a href="{{ route('system-settings.navigation.index') }}" class="ui-cement-maintenance-card">
                <span>Menu Aplikasi</span>
                <strong>Sidebar</strong>
                <p class="mt-2 text-xs leading-5 text-slate-500">Atur menu setelah login: grup, nama menu, tujuan halaman, icon, urutan, role, dan status tampil.</p>
            </a>
            <a href="{{ route('system-settings.backups.index') }}" class="ui-cement-maintenance-card">
                <span>Backup & Maintenance</span>
                <strong>Recovery</strong>
                <p class="mt-2 text-xs leading-5 text-slate-500">Buat backup database, sertakan file private, cleanup otomatis, dan cek kesehatan relasi data.</p>
            </a>
            <a href="{{ route('cement.maintenance.notification-settings.edit') }}" class="ui-cement-maintenance-card">
                <span>Email & Notifikasi</span>
                <strong>SMTP</strong>
                <p class="mt-2 text-xs leading-5 text-slate-500">Atur penerima, jadwal reminder, dan uji kirim email notifikasi sertifikat.</p>
            </a>
            <a href="{{ route('cement.maintenance.certificate-template.edit') }}" class="ui-cement-maintenance-card">
                <span>Template Sertifikat</span>
                <strong>Upload</strong>
                <p class="mt-2 text-xs leading-5 text-slate-500">Kelola template dokumen sertifikat yang dipakai sistem.</p>
            </a>
            <a href="{{ route('cement.maintenance.index') }}" class="ui-cement-maintenance-card">
                <span>Pemeliharaan Data</span>
                <strong>Master</strong>
                <p class="mt-2 text-xs leading-5 text-slate-500">Kelola data master seperti kategori, merek, lokasi, ISO, perusahaan, import, dan export.</p>
            </a>
        </section>
    </div>
</x-layouts::app>
