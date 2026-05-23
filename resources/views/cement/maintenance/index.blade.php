<x-layouts::app :title="'Pemeliharaan Data'">
    <div class="ui-page ui-cement-page">
        
        @include('admin.master-data.partials.flash-messages')

        <x-ui.page-header
            eyebrow="Admin Semen"
            title="Pemeliharaan Data"
            description="Kelola kategori, merek, sertifikat SNI, TKDN, Green Label, import, dan export data sertifikat produk semen."
        />

        <section class="ui-cement-maintenance-grid">
            <a href="{{ route('cement.maintenance.kategori-semen.index') }}" class="ui-cement-maintenance-card">
                <span>Data Kategori Semen</span>
                <strong>{{ $summary['kategori'] }}</strong>
            </a>
            <a href="{{ route('cement.maintenance.merek-semen.index') }}" class="ui-cement-maintenance-card">
                <span>Data Merek Semen</span>
                <strong>{{ $summary['merek'] }}</strong>
            </a>
            <a href="{{ route('cement.maintenance.lokasi-pabrik.index') }}" class="ui-cement-maintenance-card">
                <span>Data Lokasi Pabrik</span>
                <strong>{{ $summary['lokasi'] }}</strong>
            </a>
            <a href="{{ route('cement.maintenance.iso-standards.index') }}" class="ui-cement-maintenance-card">
                <span>Master ISO Sistem Semen</span>
                <strong>{{ $summary['iso_standards'] }}</strong>
            </a>
            @foreach ($referenceTypes as $type => $label)
                <a href="{{ route('cement.maintenance.references.index', $type) }}" class="ui-cement-maintenance-card">
                    <span>Master {{ $label }}</span>
                    <strong>{{ $referenceCounts[$type] ?? 0 }}</strong>
                </a>
            @endforeach
            <a href="{{ route('cement.maintenance.perusahaan-semen.index') }}" class="ui-cement-maintenance-card">
                <span>Perusahaan Semen</span>
                <strong>{{ $summary['perusahaan'] }}</strong>
            </a>
            <a href="{{ route('cement.maintenance.kontak-perusahaan.index') }}" class="ui-cement-maintenance-card">
                <span>Kontak Email Perusahaan</span>
                <strong>{{ $summary['kontak'] }}</strong>
            </a>
            <a href="{{ route('cement.maintenance.notification-settings.edit') }}" class="ui-cement-maintenance-card">
                <span>Pengaturan Email & Notifikasi</span>
                <strong>SMTP</strong>
            </a>
            <a href="{{ route('cement.maintenance.certificate-template.edit') }}" class="ui-cement-maintenance-card">
                <span>Template Sertifikat</span>
                <strong>Upload</strong>
            </a>
            <a href="{{ route('cement.maintenance.sertifikat-sni.index') }}" class="ui-cement-maintenance-card">
                <span>Data Sertifikat SNI</span>
                <strong>{{ $summary['sni'] }}</strong>
            </a>
            <a href="{{ route('cement.maintenance.sertifikat-tkdn.index') }}" class="ui-cement-maintenance-card">
                <span>Data Sertifikat TKDN</span>
                <strong>{{ $summary['tkdn'] }}</strong>
            </a>
            <a href="{{ route('cement.maintenance.sertifikat-green-label.index') }}" class="ui-cement-maintenance-card">
                <span>Data Green Label</span>
                <strong>{{ $summary['green_label'] }}</strong>
            </a>
            <a href="{{ route('cement.maintenance.sertifikat-sistem.index') }}" class="ui-cement-maintenance-card">
                <span>Data Sertifikat Sistem ISO</span>
                <strong>{{ $summary['system'] }}</strong>
            </a>
            <a href="{{ route('cement.import.index') }}" class="ui-cement-maintenance-card">
                <span>Import Excel</span>
                <strong>Upload</strong>
            </a>
            <a href="{{ route('cement.exports.index') }}" class="ui-cement-maintenance-card">
                <span>Export Data</span>
                <strong>Excel/PDF</strong>
            </a>
        </section>
    </div>
</x-layouts::app>
