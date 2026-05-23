@if (session('success'))
    <div class="ui-flash border-emerald-400/18 bg-emerald-400/10 text-emerald-100">
        {{ session('success') }}
    </div>
@endif

@if (session('error'))
    <div class="ui-flash border-rose-400/18 bg-rose-400/10 text-rose-100">
        {{ session('error') }}
    </div>
@endif

@if ($errors->any())
    <div class="ui-flash border-amber-400/18 bg-amber-400/10 text-amber-100">
        <p class="font-semibold text-amber-200">Periksa kembali input form.</p>
        <ul class="mt-2 space-y-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
