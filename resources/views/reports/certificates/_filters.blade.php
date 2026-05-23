<form method="GET" action="{{ route('reports.certificates.index') }}" class="ui-form-panel">
    <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-5">
        <div class="space-y-2">
            <label for="date_from" class="ui-label">Tanggal Mulai</label>
            <input id="date_from" name="date_from" type="date" value="{{ $filters['date_from'] }}" class="ui-input">
        </div>

        <div class="space-y-2">
            <label for="date_to" class="ui-label">Tanggal Akhir</label>
            <input id="date_to" name="date_to" type="date" value="{{ $filters['date_to'] }}" class="ui-input">
        </div>

        <div class="space-y-2">
            <label for="category_id" class="ui-label">Kategori</label>
            <select id="category_id" name="category_id" class="ui-select">
                <option value="">Semua kategori</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected($filters['category_id'] === $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="space-y-2">
            <label for="product_id" class="ui-label">Produk</label>
            <select id="product_id" name="product_id" class="ui-select">
                <option value="">Semua produk</option>
                @foreach ($products as $product)
                    <option value="{{ $product->id }}" @selected($filters['product_id'] === $product->id)>{{ $product->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="space-y-2">
            <label for="status" class="ui-label">Status</label>
            <select id="status" name="status" class="ui-select">
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="mt-6 flex flex-wrap items-center gap-3">
        <button type="submit" class="ui-button-primary">
            Terapkan Filter
        </button>

        <a href="{{ route('reports.certificates.index') }}" class="ui-button-secondary">
            Reset
        </a>
    </div>
</form>
