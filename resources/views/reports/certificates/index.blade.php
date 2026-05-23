<x-layouts::app :title="'Laporan Monitoring Sertifikat'">
    <div class="ui-page">
        <x-ui.page-header
            eyebrow="Laporan Monitoring"
            title="Laporan Monitoring Sertifikat"
            description="Filter laporan berdasarkan tanggal habis berlaku, kategori, produk, dan status monitoring sertifikat."
        >
            <x-slot:actions>
                <a href="{{ route('reports.certificates.export-pdf', request()->query()) }}" class="ui-button-secondary">
                    Export PDF
                </a>
                <a href="{{ route('reports.certificates.export-excel', request()->query()) }}" class="ui-button-primary">
                    Export Excel
                </a>
            </x-slot:actions>
        </x-ui.page-header>

        @include('admin.master-data.partials.flash-messages')
        @include('reports.certificates._filters')

        <section class="ui-stat-grid">
            <x-ui.metric-card label="Total Data" :value="$summary['total']" />
            <x-ui.metric-card label="Aktif" :value="$summary['active']" tone="success" />
            <x-ui.metric-card label="Akan Habis" :value="$summary['expiring_soon']" tone="warning" />
            <x-ui.metric-card label="Habis" :value="$summary['expired']" tone="danger" />
        </section>

        @include('reports.certificates._table', ['certificates' => $report])

        <div class="ui-pagination-card">
            {{ $report->links() }}
        </div>
    </div>
</x-layouts::app>
