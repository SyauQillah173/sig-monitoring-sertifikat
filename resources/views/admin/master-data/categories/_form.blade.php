@csrf

<div class="grid gap-5 md:grid-cols-2">
    <div class="space-y-2">
        <label for="name" class="ui-label">Nama Kategori</label>
        <input
            id="name"
            name="name"
            type="text"
            value="{{ old('name', $category->name) }}"
            class="ui-input"
            placeholder="Contoh: Semen Portland Komposit (PCC)"
            required
        >
    </div>

    <div class="space-y-2">
        <label for="slug" class="ui-label">Slug</label>
        <input
            id="slug"
            name="slug"
            type="text"
            value="{{ old('slug', $category->slug) }}"
            class="ui-input"
            placeholder="semen-portland-komposit-pcc"
            required
        >
    </div>
</div>

<div class="mt-5 space-y-2">
    <label for="description" class="ui-label">Deskripsi</label>
    <textarea
        id="description"
        name="description"
        rows="4"
        class="ui-textarea"
        placeholder="Deskripsi singkat kategori produk"
    >{{ old('description', $category->description) }}</textarea>
</div>

<div class="mt-5 space-y-2">
    <label for="is_active" class="ui-label">Status</label>
    <select id="is_active" name="is_active" class="ui-select">
        <option value="1" @selected((int) old('is_active', $category->is_active ?? 1) === 1)>Aktif</option>
        <option value="0" @selected((int) old('is_active', $category->is_active ?? 1) === 0)>Nonaktif</option>
    </select>
</div>

<div class="mt-6 flex flex-wrap items-center gap-3">
    <button type="submit" class="ui-button-primary">
        {{ $submitLabel }}
    </button>

    <a href="{{ route('admin.categories.index') }}" class="ui-button-secondary">
        Kembali
    </a>
</div>
