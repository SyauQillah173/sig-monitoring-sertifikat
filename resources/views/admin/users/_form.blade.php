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

<div class="mt-6 flex flex-wrap gap-3">
    <button class="ui-button-primary">{{ $submitLabel }}</button>
    <a href="{{ route('admin.users.index') }}" class="ui-button-secondary">Batal</a>
</div>
