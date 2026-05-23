@php($options = collect($references[$type] ?? []))
<div class="space-y-2">
    <label class="ui-label" for="{{ $name }}">{{ $label }}</label>
    <select id="{{ $name }}" name="{{ $name }}" class="ui-select" required>
        <option value="">Pilih {{ strtolower($label) }}</option>
        @foreach ($options as $option)
            <option value="{{ $option->id }}" @selected((int) old($name, $value) === $option->id)>
                ID #{{ $option->id }} - {{ $option->name }}
            </option>
        @endforeach
    </select>
    @if ($options->isEmpty())
        <p class="ui-input-hint">Belum ada master {{ strtolower($label) }} aktif. Tambahkan dulu di Pemeliharaan Data.</p>
    @else
        <p class="ui-input-hint">Pilihan ini disimpan sebagai ID master dari tabel cement_reference_values.</p>
    @endif
</div>
