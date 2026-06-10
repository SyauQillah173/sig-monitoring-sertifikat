<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\Auth\PasswordResetCodeService;
use App\Services\SystemNavigationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Throwable;

class UserManagementController extends Controller
{
    public function __construct(
        private readonly SystemNavigationService $navigation,
    ) {}

    public function index(Request $request): View
    {
        $this->authorizeFullAccess($request);

        return view('admin.users.index', [
            'users' => User::query()
                ->with('navigationItems')
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

    public function create(Request $request): View
    {
        $this->authorizeFullAccess($request);

        return view('admin.users.create', [
            'user' => new User,
            'roles' => UserRole::cases(),
            ...$this->accessViewData(new User),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeFullAccess($request);
        $payload = $this->validatedPayload($request);
        $access = $this->accessPayload($payload);

        $user = new User;
        $user->forceFill([
            'name' => $payload['name'],
            'email' => $payload['email'],
            'password' => Hash::make($payload['password']),
            'role' => $payload['role'],
            'is_active' => (bool) $payload['is_active'],
            'is_super_admin' => $access['is_super_admin'],
            'has_custom_access' => $access['has_custom_access'],
            'email_verified_at' => now(),
        ])->save();
        $user->assignAppRole($payload['role']);
        $user->navigationItems()->sync($access['navigation_item_ids']);

        app(AuditLogger::class)->log('admin_user_created', $user, 'Admin menambahkan user aplikasi.', null, [
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role?->value,
            'is_active' => $user->is_active,
            'is_super_admin' => $user->is_super_admin,
            'has_custom_access' => $user->has_custom_access,
            'navigation_item_ids' => $access['navigation_item_ids'],
        ]);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User '.$user->email.' berhasil ditambahkan.');
    }

    public function edit(Request $request, User $user): View
    {
        $this->authorizeFullAccess($request);

        return view('admin.users.edit', [
            'user' => $user->load('navigationItems'),
            'roles' => UserRole::cases(),
            ...$this->accessViewData($user),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorizeFullAccess($request);
        $payload = $this->validatedPayload($request, $user);
        $oldValues = [
            ...$user->only(['name', 'email', 'role', 'is_active', 'is_super_admin', 'has_custom_access']),
            'navigation_item_ids' => $user->navigationItems()->pluck('navigation_items.id')->all(),
        ];
        $isSelf = $request->user()->is($user);
        $access = $isSelf
            ? [
                'role' => $user->appRole()?->value ?? $payload['role'],
                'is_super_admin' => (bool) $user->is_super_admin,
                'has_custom_access' => (bool) $user->has_custom_access,
                'navigation_item_ids' => $oldValues['navigation_item_ids'],
            ]
            : [
                'role' => $payload['role'],
                ...$this->accessPayload($payload),
            ];

        $user->forceFill([
            'name' => $payload['name'],
            'email' => $payload['email'],
            'role' => $access['role'],
            'is_active' => $isSelf ? true : (bool) $payload['is_active'],
            'is_super_admin' => $access['is_super_admin'],
            'has_custom_access' => $access['has_custom_access'],
            'email_verified_at' => $user->email === $payload['email'] ? $user->email_verified_at : now(),
        ]);

        if (filled($payload['password'] ?? null)) {
            $user->password = Hash::make($payload['password']);
        }

        $user->save();
        $user->assignAppRole($access['role']);
        $user->navigationItems()->sync($access['navigation_item_ids']);

        app(AuditLogger::class)->log('admin_user_updated', $user, 'Admin memperbarui user aplikasi.', $oldValues, [
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role?->value,
            'is_active' => $user->is_active,
            'is_super_admin' => $user->is_super_admin,
            'has_custom_access' => $user->has_custom_access,
            'navigation_item_ids' => $access['navigation_item_ids'],
            'password_changed' => filled($payload['password'] ?? null),
        ]);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User '.$user->email.' berhasil diperbarui.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->authorizeFullAccess($request);

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
        $this->authorizeFullAccess(request());

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
     * @return array{name: string, email: string, role: string, is_active: bool, access_mode: string|null, navigation_items: array<int, int>, password?: string|null}
     */
    private function validatedPayload(Request $request, ?User $user = null): array
    {
        $payload = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user),
            ],
            'role' => ['required', Rule::in(UserRole::values())],
            'is_active' => ['required', 'boolean'],
            'access_mode' => ['nullable', Rule::in(['full', 'custom'])],
            'navigation_items' => ['nullable', 'array'],
            'navigation_items.*' => ['integer', Rule::exists('navigation_items', 'id')],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
        ]);

        $payload['navigation_items'] = collect($payload['navigation_items'] ?? [])
            ->map(fn (int|string $id): int => (int) $id)
            ->unique()
            ->values()
            ->all();

        return $payload;
    }

    /**
     * @return array{is_super_admin: bool, has_custom_access: bool, navigation_item_ids: list<int>}
     */
    private function accessPayload(array $payload): array
    {
        if (($payload['role'] ?? null) === UserRole::Admin->value && ($payload['access_mode'] ?? 'custom') === 'full') {
            return [
                'is_super_admin' => true,
                'has_custom_access' => false,
                'navigation_item_ids' => [],
            ];
        }

        $role = UserRole::tryFrom((string) ($payload['role'] ?? '')) ?? UserRole::Petugas;
        $allowedIds = $this->navigation->accessItems()
            ->filter(fn ($item): bool => in_array($role->value, $item->allowed_roles ?? [], true))
            ->pluck('id')
            ->all();

        $navigationItemIds = collect($payload['navigation_items'] ?? [])
            ->intersect($allowedIds)
            ->values()
            ->all();

        return [
            'is_super_admin' => false,
            'has_custom_access' => true,
            'navigation_item_ids' => $navigationItemIds,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function accessViewData(User $user): array
    {
        $role = old('role', $user->role?->value ?: UserRole::Petugas->value);
        $accessMode = old('access_mode', $user->exists && $user->hasFullSystemAccess() ? 'full' : 'custom');
        $selectedNavigationIds = old(
            'navigation_items',
            $user->exists && $user->has_custom_access
                ? $user->navigationItems->pluck('id')->all()
                : $this->navigation->defaultAccessItemIdsForRole($role),
        );

        return [
            'accessItems' => $this->navigation->accessItems(),
            'selectedNavigationIds' => collect($selectedNavigationIds)->map(fn (int|string $id): int => (int) $id)->all(),
            'accessMode' => $accessMode,
        ];
    }

    private function authorizeFullAccess(Request $request): void
    {
        abort_unless($request->user()?->hasFullSystemAccess(), 403);
    }
}
