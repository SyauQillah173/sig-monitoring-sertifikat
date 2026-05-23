<x-layouts::app :title="'Export Data'">
    <div class="ui-page ui-cement-page">
        
        <x-ui.page-header
            eyebrow="Sertifikat Semen"
            title="Export Data"
            description="Unduh data sertifikat semen dalam format Excel atau PDF."
        />

        <section class="ui-cement-maintenance-grid">
            <a href="{{ route('cement.exports.sni') }}" class="ui-cement-maintenance-card"><span>Export Sertifikat SNI</span><strong>Excel</strong></a>
            <a href="{{ route('cement.exports.tkdn') }}" class="ui-cement-maintenance-card"><span>Export Sertifikat TKDN</span><strong>Excel</strong></a>
            <a href="{{ route('cement.exports.green-label') }}" class="ui-cement-maintenance-card"><span>Export Green Label</span><strong>Excel</strong></a>
            <a href="{{ route('cement.exports.all') }}" class="ui-cement-maintenance-card"><span>Export Semua Data</span><strong>Excel</strong></a>
            <a href="{{ route('cement.exports.pdf') }}" class="ui-cement-maintenance-card"><span>Laporan Sertifikat</span><strong>PDF</strong></a>
            <a href="{{ route('cement.exports.template') }}" class="ui-cement-maintenance-card"><span>Template Import</span><strong>Download</strong></a>
        </section>
    </div>
</x-layouts::app>
