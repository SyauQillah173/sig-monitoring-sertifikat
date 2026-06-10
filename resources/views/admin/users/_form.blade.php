@csrf

<div class="grid gap-5 md:grid-cols-2">
    <div class="space-y-2">
        <label for="name" class="ui-label">Nama User</label>
        <input id="name" name="name" value="{{ old('name', $user->name) }}" class="ui-input" placeholder="Nama lengkap" required>
    </div>

    <div class="space-y-2">
        <label for="email" class="ui-label">Email Login</label>
        <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" class="ui-input" placeholder="nama@gmail.com" required>
        <p class="ui-input-hint">Email ini dipakai untuk login dan menerima link reset password.</p>
    </div>

    <div class="space-y-2">
        <label for="role" class="ui-label">Role</label>
        <select id="role" name="role" class="ui-select" required>
            @foreach ($roles as $role)
                <option value="{{ $role->value }}" @selected(old('role', $user->role?->value) === $role->value)>
                    {{ $role->label() }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="space-y-2">
        <label for="is_active" class="ui-label">Status Akses</label>
        <select id="is_active" name="is_active" class="ui-select" required @disabled($user->exists && auth()->user()->is($user))>
            <option value="1" @selected((string) old('is_active', $user->exists ? (int) $user->is_active : 1) === '1')>Aktif</option>
            <option value="0" @selected((string) old('is_active', $user->exists ? (int) $user->is_active : 1) === '0')>Nonaktif</option>
        </select>
        @if ($user->exists && auth()->user()->is($user))
            <input type="hidden" name="is_active" value="1">
            <p class="ui-input-hint">Akun yang sedang login tidak bisa dinonaktifkan dari form ini.</p>
        @else
            <p class="ui-input-hint">User nonaktif tidak dapat login, tetapi riwayat datanya tetap tersimpan.</p>
        @endif
    </div>
</div>

<div class="mt-6 grid gap-5 md:grid-cols-2">
    <div class="space-y-2">
        <label for="password" class="ui-label">{{ $user->exists ? 'Password Baru' : 'Password Awal' }}</label>
        <input id="password" name="password" type="password" class="ui-input" autocomplete="new-password" @required(! $user->exists)>
        @if ($user->exists)
            <p class="ui-input-hint">Kosongkan jika password tidak ingin diganti.</p>
        @else
            <p class="ui-input-hint">User dapat mengganti sendiri lewat menu reset password setelah SMTP aktif.</p>
        @endif
    </div>

    <div class="space-y-2">
        <label for="password_confirmation" class="ui-label">Konfirmasi Password</label>
        <input id="password_confirmation" name="password_confirmation" type="password" class="ui-input" autocomplete="new-password" @required(! $user->exists)>
    </div>
</div>

<section class="mt-6 rounded-[1.35rem] border border-slate-200/80 bg-slate-50/80 p-4 dark:border-white/10 dark:bg-white/5">
    <div class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
        <div>
            <h2 class="ui-title-sm">Hak Akses User</h2>
            <p class="ui-input-hint mt-1">Atur menu yang muncul setelah user login. Admin utama tetap memiliki akses penuh ke seluruh sistem.</p>
        </div>

        @if ($user->exists && auth()->user()->is($user))
            <span class="ui-badge ui-badge-warning">Akun sedang login</span>
        @endif
    </div>

    @if ($user->exists && auth()->user()->is($user))
        <p class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm leading-6 text-amber-900 dark:border-amber-300/20 dark:bg-amber-300/10 dark:text-amber-100">
            Role dan hak akses akun yang sedang login dipertahankan otomatis agar admin tidak kehilangan akses saat menyimpan profil user sendiri.
        </p>
    @endif

    <div class="mt-5 grid gap-3 md:grid-cols-2">
        <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 bg-white p-4 text-sm dark:border-white/10 dark:bg-slate-950/40">
            <input type="radio" name="access_mode" value="full" class="mt-1" @checked($accessMode === 'full') @disabled($user->exists && auth()->user()->is($user))>
            <span>
                <span class="block font-semibold text-slate-950 dark:text-white">Admin Utama / Full Access</span>
                <span class="mt-1 block text-slate-500 dark:text-slate-400">Khusus role Administrator. Bisa mengatur semua menu, user, hak akses, backup, dan CMS.</span>
            </span>
        </label>

        <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 bg-white p-4 text-sm dark:border-white/10 dark:bg-slate-950/40">
            <input type="radio" name="access_mode" value="custom" class="mt-1" @checked($accessMode !== 'full') @disabled($user->exists && auth()->user()->is($user))>
            <span>
                <span class="block font-semibold text-slate-950 dark:text-white">Akses Khusus</span>
                <span class="mt-1 block text-slate-500 dark:text-slate-400">User hanya melihat dan membuka menu yang dicentang di bawah ini.</span>
            </span>
        </label>
    </div>

    @if ($user->exists && auth()->user()->is($user))
        <input type="hidden" name="access_mode" value="{{ $accessMode }}">
    @endif

    <div class="mt-5 grid gap-4 lg:grid-cols-2">
        @foreach ($accessItems->groupBy('group_label') as $groupLabel => $items)
            <div class="rounded-xl border border-slate-200 bg-white p-4 dark:border-white/10 dark:bg-slate-950/40">
                <p class="text-xs font-bold tracking-[0.14em] text-slate-500 uppercase dark:text-slate-400">{{ $groupLabel }}</p>

                <div class="mt-3 space-y-2">
                    @foreach ($items as $item)
                        <label class="flex items-center justify-between gap-3 rounded-lg border border-slate-200/80 bg-slate-50 px-3 py-2 text-sm font-semibold text-slate-700 dark:border-white/8 dark:bg-white/5 dark:text-slate-200">
                            <span>{{ $item->label }}</span>
                            <input
                                type="checkbox"
                                name="navigation_items[]"
                                value="{{ $item->id }}"
                                @checked(in_array($item->id, $selectedNavigationIds, true))
                                @disabled($user->exists && auth()->user()->is($user))
                            >
                        </label>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</section>

<div class="mt-6 flex flex-wrap gap-3">
    <button class="ui-button-primary">{{ $submitLabel }}</button>
    <a href="{{ route('admin.users.index') }}" class="ui-button-secondary">Batal</a>
</div>
