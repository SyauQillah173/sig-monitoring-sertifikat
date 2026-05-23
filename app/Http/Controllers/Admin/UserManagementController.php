<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\Auth\PasswordResetCodeService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Throwable;

class UserManagementController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.users.index', [
            'users' => User::query()
                ->when($request->filled('search'), function ($query) use ($request) {
                    $search = '%'.$request->string('search').'%';
                    $query->where(fn ($subQuery) => $subQuery
                        ->where('name', 'like', $search)
                        ->orWhere('email', 'like', $search));
                })
                ->orderBy('role')
                ->orderBy('name')
                ->paginate(10)
                ->withQueryString(),
            'roles' => UserRole::cases(),
        ]);
    }

    public function create(): View
    {
        return view('admin.users.create', [
            'user' => new User,
            'roles' => UserRole::cases(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $payload = $this->validatedPayload($request);

        $user = new User;
        $user->forceFill([
            'name' => $payload['name'],
            'email' => $payload['email'],
            'password' => Hash::make($payload['password']),
            'role' => $payload['role'],
            'is_active' => (bool) $payload['is_active'],
            'email_verified_at' => now(),
        ])->save();
        $user->assignAppRole($payload['role']);

        app(AuditLogger::class)->log('admin_user_created', $user, 'Admin menambahkan user aplikasi.', null, [
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role?->value,
            'is_active' => $user->is_active,
        ]);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User '.$user->email.' berhasil ditambahkan.');
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', [
            'user' => $user,
            'roles' => UserRole::cases(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $payload = $this->validatedPayload($request, $user);
        $oldValues = $user->only(['name', 'email', 'role', 'is_active']);
        $isSelf = $request->user()->is($user);

        $user->forceFill([
            'name' => $payload['name'],
            'email' => $payload['email'],
            'role' => $payload['role'],
            'is_active' => $isSelf ? true : (bool) $payload['is_active'],
            'email_verified_at' => $user->email === $payload['email'] ? $user->email_verified_at : now(),
        ]);

        if (filled($payload['password'] ?? null)) {
            $user->password = Hash::make($payload['password']);
        }

        $user->save();
        $user->assignAppRole($payload['role']);

        app(AuditLogger::class)->log('admin_user_updated', $user, 'Admin memperbarui user aplikasi.', $oldValues, [
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role?->value,
            'is_active' => $user->is_active,
            'password_changed' => filled($payload['password'] ?? null),
        ]);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User '.$user->email.' berhasil diperbarui.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($request->user()->is($user)) {
            return back()->with('error', 'Akun yang sedang login tidak boleh dihapus dari menu ini.');
        }

        $oldValues = $user->only(['name', 'email', 'role']);
        $email = $user->email;

        $user->delete();

        app(AuditLogger::class)->log('admin_user_deleted', null, 'Admin menghapus user aplikasi.', $oldValues);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User '.$email.' berhasil dihapus.');
    }

    public function sendResetLink(User $user, PasswordResetCodeService $resetCodeService): RedirectResponse
    {
        try {
            $resetCodeService->send($user);
        } catch (Throwable $throwable) {
            report($throwable);

            return back()->with('error', 'Kode reset password gagal dikirim. Periksa SMTP dan MAIL_PASSWORD/app password di .env.');
        }

        app(AuditLogger::class)->log('admin_user_reset_code_sent', $user, 'Admin mengirim kode reset password user.', null, [
            'email' => $user->email,
        ]);

        return back()->with('success', 'Kode reset password berhasil dikirim ke '.$user->email.'.');
    }

    /**
     * @return array{name: string, email: string, role: string, is_active: bool, password?: string|null}
     */
    private function validatedPayload(Request $request, ?User $user = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user),
            ],
            'role' => ['required', Rule::in(UserRole::values())],
            'is_active' => ['required', 'boolean'],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
        ]);
    }
}
