@php($filters = $dashboard['filters'])
@php($summary = $dashboard['summary'])
@php($selectedKategori = $filters['kategori'])
@php($selectedMerek = $filters['merek'])

<x-layouts::app :title="'Sertifikat Produk'">
    <div class="ui-page ui-cement-page">
        
        @include('admin.master-data.partials.flash-messages')

        <form id="cementDashboardFilter" method="GET" action="{{ route('cement.products.index') }}" class="ui-cement-dashboard">
            <aside class="ui-cement-tree-panel">
                <div class="ui-cement-panel-head">
                    <div>
                        <p class="ui-dashboard-kicker">Daftar Merek</p>
                        <h2>Produk Semen</h2>
                    </div>
                    <a href="{{ route('cement.products.index') }}" class="ui-cement-icon-button" title="Reset filter">
                        <flux:icon name="arrow-path" variant="outline" class="size-4" />
                    </a>
                </div>

                <div class="ui-cement-tree">
                    @foreach ($dashboard['kategoriTree'] as $category)
                        @php($categoryBrandIds = $category->merekSemen->pluck('id')->all())
                        @php($categoryChecked = in_array($category->id, $selectedKategori, true) || collect($categoryBrandIds)->diff($selectedMerek)->isEmpty())
                        <section class="ui-cement-tree-group">
                            <label class="ui-cement-check ui-cement-check-parent">
                                <input
                                    type="checkbox"
                                    name="kategori[]"
                                    value="{{ $category->id }}"
                                    data-cement-category
                                    @checked($categoryChecked)
                                >
                                <span>{{ $category->nama_kategori }}</span>
                            </label>

                            <div class="ui-cement-tree-children">
                                @foreach ($category->merekSemen as $brand)
                                    <label class="ui-cement-check">
                                        <input
                                            type="checkbox"
                                            name="merek[]"
                                            value="{{ $brand->id }}"
                                            data-cement-brand
                                            @checked(in_array($brand->id, $selectedMerek, true) || in_array($category->id, $selectedKategori, true))
                                        >
                                        <span>{{ $brand->nama_merek }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </section>
                    @endforeach
                </div>
            </aside>

            <section class="ui-cement-main-panel">
                <div class="ui-cement-toolbar">
                    <div class="ui-cement-chart-card">
                        <div class="ui-cement-donut" style="--cement-donut: {{ $dashboard['chart']['gradient'] }}"></div>
                        <div class="ui-cement-chart-legend">
                            @foreach ($dashboard['chart']['items'] as $item)
                                <span><i style="background: {{ $item['color'] }}"></i>{{ $item['label'] }} {{ number_format($item['percent'], 1) }}%</span>
                            @endforeach
                        </div>
                    </div>

                    <div class="ui-cement-filter-card">
                        <div class="grid gap-3 md:grid-cols-2">
                            <label class="space-y-1">
                                <span class="ui-label">SNI</span>
                                <select name="sni" class="ui-select" data-cement-auto-submit>
                                    <option value="all">Semua</option>
                                    @foreach ($dashboard['options']['sni'] as $option)
                                        <option value="{{ $option }}" @selected($filters['sni'] === $option)>{{ $option }}</option>
                                    @endforeach
                                </select>
                            </label>

                            <label class="space-y-1">
                                <span class="ui-label">LSPro (khusus SNI)</span>
                                <select name="lspro" class="ui-select" data-cement-auto-submit>
                                    <option value="all">Semua</option>
                                    @foreach ($dashboard['options']['lspro'] as $option)
                                        <option value="{{ $option }}" @selected($filters['lspro'] === $option)>{{ $option }}</option>
                                    @endforeach
                                </select>
                            </label>

                            <label class="space-y-1">
                                <span class="ui-label">Lokasi</span>
                                <select name="lokasi" class="ui-select" data-cement-auto-submit>
                                    <option value="all">Semua</option>
                                    @foreach ($dashboard['options']['lokasi'] as $option)
                                        <option value="{{ $option }}" @selected($filters['lokasi'] === $option)>{{ $option }}</option>
                                    @endforeach
                                </select>
                            </label>

                            <label class="space-y-1">
                                <span class="ui-label">Status</span>
                                <select name="status" class="ui-select" data-cement-auto-submit>
                                    @foreach ($dashboard['options']['status'] as $value => $label)
                                        <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </label>
                        </div>

                        <div class="mt-4 flex flex-wrap gap-2">
                            <button type="submit" class="ui-button-primary">Terapkan Filter</button>
                            <a href="{{ route('cement.products.index') }}" class="ui-button-secondary">
                                <flux:icon name="arrow-path" variant="outline" class="size-4" />
                                Eraser
                            </a>
                            @if (auth()->user()->hasAppRole(\App\Enums\UserRole::Admin))
                                <a href="{{ route('cement.exports.all', request()->query()) }}" class="ui-button-secondary">Export Excel</a>
                                <a href="{{ route('cement.exports.pdf', request()->query()) }}" class="ui-button-secondary">Export PDF</a>
                            @endif
                        </div>
                    </div>
                </div>

                <section class="ui-cement-summary-grid">
                    <article><span>Total Merek</span><strong>{{ $summary['total_merek'] }}</strong></article>
                    <article><span>Sertifikat SNI</span><strong>{{ $summary['total_sni'] }}</strong></article>
                    <article><span>Sertifikat TKDN</span><strong>{{ $summary['total_tkdn'] }}</strong></article>
                    <article><span>Green Label</span><strong>{{ $summary['total_green_label'] }}</strong></article>
                </section>

                @include('cement.partials.product-tables')
            </section>
        </form>
    </div>

    <script>
        const syncCementParentState = (group) => {
            const parent = group?.querySelector('[data-cement-category]');
            const brands = Array.from(group?.querySelectorAll('[data-cement-brand]') ?? []);
            if (!parent || brands.length === 0) return;

            const checkedCount = brands.filter((checkbox) => checkbox.checked).length;
            parent.checked = checkedCount === brands.length;
            parent.indeterminate = checkedCount > 0 && checkedCount < brands.length;
        };

        document.querySelectorAll('.ui-cement-tree-group').forEach(syncCementParentState);

        document.addEventListener('change', function (event) {
            const form = document.getElementById('cementDashboardFilter');
            if (!form) return;

            if (event.target.matches('[data-cement-category]')) {
                const group = event.target.closest('.ui-cement-tree-group');
                group?.querySelectorAll('[data-cement-brand]').forEach((checkbox) => {
                    checkbox.checked = event.target.checked;
                });
                event.target.indeterminate = false;
                form.requestSubmit();
            }

            if (event.target.matches('[data-cement-brand]')) {
                syncCementParentState(event.target.closest('.ui-cement-tree-group'));
                form.requestSubmit();
            }

            if (event.target.matches('[data-cement-auto-submit]')) {
                form.requestSubmit();
            }
        });
    </script>
</x-layouts::app>
