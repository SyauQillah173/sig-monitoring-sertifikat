<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\NavigationItem;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

class SystemNavigationService
{
    /**
     * @return array<int, array{label: string, items: array<int, array<string, mixed>>}>
     */
    public function groupsForUser(User $user): array
    {
        $items = $this->databaseItemsForUser($user);

        if ($items->isEmpty()) {
            $items = collect($this->defaultItems())
                ->filter(fn (array $item) => $this->userCanSee($user, $item))
                ->values();
        }

        return $this->groupItems($items);
    }

    public function ensureDefaultsExist(): void
    {
        $defaults = collect($this->defaultItems());

        try {
            NavigationItem::query()
                ->whereIn('route_name', $this->deprecatedRouteNames())
                ->where('is_active', true)
                ->update(['is_active' => false]);

            $existingRouteNames = NavigationItem::query()
                ->whereIn('route_name', $defaults->pluck('route_name'))
                ->pluck('route_name')
                ->all();
        } catch (QueryException) {
            return;
        }

        $now = now();

        $missingRows = $defaults
            ->reject(fn (array $item): bool => in_array($item['route_name'], $existingRouteNames, true))
            ->map(fn (array $item): array => [
                'route_name' => $item['route_name'],
                'group_label' => $item['group_label'],
                'label' => $item['label'],
                'icon' => $item['icon'],
                'sort_order' => $item['sort_order'],
                'allowed_roles' => json_encode(array_values($item['allowed_roles'])),
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->values()
            ->all();

        if ($missingRows === []) {
            return;
        }

        NavigationItem::query()->insert($missingRows);

        $this->clearCache();
    }

    public function clearCache(): void
    {
        foreach (UserRole::values() as $role) {
            Cache::forget($this->cacheKey($role));
        }
    }

    public function canAccessRoute(User $user, ?string $routeName, Request $request): bool
    {
        if (! $routeName || $this->isAlwaysAllowedRoute($routeName, $request)) {
            return true;
        }

        if (! $user->has_custom_access || $user->hasFullSystemAccess()) {
            return true;
        }

        $items = $this->databaseItemsForUser($user);

        return $items->contains(fn (array $item) => $this->routeMatchesItem($routeName, $item, $request));
    }

    /**
     * @return array<string, string>
     */
    public function availableIcons(): array
    {
        return [
            'home' => 'Beranda',
            'clipboard-document-check' => 'Sertifikat / Checklist',
            'document-check' => 'Dokumen Disetujui',
            'document-text' => 'Dokumen Teks',
            'folder-open' => 'Folder / Import',
            'chart-bar' => 'Grafik / Laporan',
            'bell' => 'Notifikasi',
            'cog-6-tooth' => 'Pengaturan Akun',
            'adjustments-horizontal' => 'Konfigurasi',
            'bars-3-bottom-left' => 'Menu Aplikasi',
            'users' => 'Manajemen User',
            'user-group' => 'Grup User',
            'paint-brush' => 'Tampilan Publik',
            'wrench-screwdriver' => 'Pemeliharaan',
            'building-office-2' => 'Instansi / Perusahaan',
            'map-pin' => 'Lokasi',
            'envelope' => 'Email',
            'shield-check' => 'Keamanan',
            'arrow-up-tray' => 'Upload',
            'arrow-down-tray' => 'Download',
            'archive-box' => 'Backup / Arsip',
        ];
    }

    /**
     * @return array<int, string>
     */
    public function availableIconNames(): array
    {
        return array_keys($this->availableIcons());
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function defaultItems(): array
    {
        return [
            ['group_label' => 'Platform', 'label' => 'Sertifikat Produk', 'route_name' => 'cement.products.index', 'icon' => 'home', 'sort_order' => 10, 'allowed_roles' => UserRole::values()],
            ['group_label' => 'Platform', 'label' => 'Sertifikat Sistem', 'route_name' => 'cement.system.index', 'icon' => 'clipboard-document-check', 'sort_order' => 20, 'allowed_roles' => UserRole::values()],
            ['group_label' => 'Platform', 'label' => 'Pengaturan Akun', 'route_name' => 'profile.edit', 'icon' => 'cog-6-tooth', 'sort_order' => 30, 'allowed_roles' => UserRole::values()],
            ['group_label' => 'Platform', 'label' => 'Notifikasi Internal', 'route_name' => 'notifications.index', 'icon' => 'bell', 'sort_order' => 40, 'allowed_roles' => UserRole::values()],
            ['group_label' => 'Pengaturan Sistem', 'label' => 'Konfigurasi Sistem', 'route_name' => 'system-settings.index', 'icon' => 'adjustments-horizontal', 'sort_order' => 100, 'allowed_roles' => [UserRole::Admin->value]],
            ['group_label' => 'Pengaturan Sistem', 'label' => 'Manajemen User', 'route_name' => 'admin.users.index', 'icon' => 'users', 'sort_order' => 110, 'allowed_roles' => [UserRole::Admin->value]],
            ['group_label' => 'Pengaturan Sistem', 'label' => 'Tampilan Publik', 'route_name' => 'system-settings.public-appearance.edit', 'icon' => 'paint-brush', 'sort_order' => 120, 'allowed_roles' => [UserRole::Admin->value]],
            ['group_label' => 'Pengaturan Sistem', 'label' => 'Menu Aplikasi', 'route_name' => 'system-settings.navigation.index', 'icon' => 'bars-3-bottom-left', 'sort_order' => 130, 'allowed_roles' => [UserRole::Admin->value]],
            ['group_label' => 'Pengaturan Sistem', 'label' => 'Pemeliharaan Data', 'route_name' => 'cement.maintenance.index', 'icon' => 'wrench-screwdriver', 'sort_order' => 140, 'allowed_roles' => [UserRole::Admin->value]],
            ['group_label' => 'Pengaturan Sistem', 'label' => 'Backup & Maintenance', 'route_name' => 'system-settings.backups.index', 'icon' => 'archive-box', 'sort_order' => 150, 'allowed_roles' => [UserRole::Admin->value]],
            ['group_label' => 'Monitoring', 'label' => 'Import Excel', 'route_name' => 'cement.import.index', 'icon' => 'folder-open', 'sort_order' => 200, 'allowed_roles' => [UserRole::Admin->value]],
            ['group_label' => 'Laporan', 'label' => 'Export Data', 'route_name' => 'cement.exports.index', 'icon' => 'chart-bar', 'sort_order' => 300, 'allowed_roles' => [UserRole::Admin->value]],
        ];
    }

    /**
     * @return Collection<int, NavigationItem>
     */
    public function accessItems(): Collection
    {
        $this->ensureDefaultsExist();

        try {
            return NavigationItem::query()
                ->where('is_active', true)
                ->whereNotIn('route_name', $this->fullAccessOnlyRouteNames())
                ->ordered()
                ->get();
        } catch (QueryException) {
            return collect();
        }
    }

    /**
     * @return list<int>
     */
    public function defaultAccessItemIdsForRole(UserRole|string|null $role): array
    {
        $role = $role instanceof UserRole ? $role->value : (string) $role;

        if ($role === '') {
            return [];
        }

        return $this->accessItems()
            ->filter(fn (NavigationItem $item): bool => in_array($role, $item->allowed_roles ?? [], true))
            ->pluck('id')
            ->all();
    }

    private function databaseItemsForUser(User $user): Collection
    {
        try {
            $items = NavigationItem::query()
                ->where('is_active', true)
                ->whereNotIn('route_name', $this->deprecatedRouteNames())
                ->ordered()
                ->get();
        } catch (QueryException) {
            return collect();
        }

        if ($user->has_custom_access && ! $user->hasFullSystemAccess()) {
            $allowedIds = $user->navigationItems()->pluck('navigation_items.id')->all();
            $items = $items->whereIn('id', $allowedIds);
        }

        return $items
            ->filter(fn (NavigationItem $item) => Route::has($item->route_name))
            ->filter(fn (NavigationItem $item) => $this->userCanSee($user, $item->toArray()))
            ->map(fn (NavigationItem $item) => $item->toArray())
            ->values();
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function userCanSee(User $user, array $item): bool
    {
        if ($user->hasFullSystemAccess()) {
            return true;
        }

        return in_array($user->appRole()?->value, $item['allowed_roles'] ?? [], true);
    }

    private function groupItems(Collection $items): array
    {
        return $items
            ->map(fn ($item) => $item instanceof NavigationItem ? $item->toArray() : $item)
            ->map(fn (array $item) => [
                'group_label' => $item['group_label'],
                'label' => $item['label'],
                'route' => route($item['route_name']),
                'icon' => $item['icon'] ?: 'circle',
                'current' => request()->routeIs($this->activePattern($item['route_name'])),
            ])
            ->groupBy('group_label')
            ->map(fn ($group, string $label) => [
                'label' => $label,
                'items' => collect($group)->values()->all(),
            ])
            ->values()
            ->all();
    }

    private function activePattern(string $routeName): string
    {
        return str_ends_with($routeName, '.index')
            ? substr($routeName, 0, -6).'.*'
            : $routeName;
    }

    private function routeMatchesItem(string $routeName, array $item, Request $request): bool
    {
        $itemRouteName = (string) ($item['route_name'] ?? '');

        if ($routeName === $itemRouteName) {
            return true;
        }

        if ($routeName === 'cement.certificates.document' || $routeName === 'cement.certificates.download') {
            $type = (string) $request->route('type');

            return match ($itemRouteName) {
                'cement.products.index' => in_array($type, ['sni', 'tkdn', 'green-label'], true),
                'cement.system.index' => $type === 'system',
                default => false,
            };
        }

        foreach ($this->routePrefixesForItem($itemRouteName) as $prefix) {
            if (str_starts_with($routeName, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private function routePrefixesForItem(string $routeName): array
    {
        return match ($routeName) {
            'cement.products.index' => ['cement.products.'],
            'cement.system.index' => ['cement.system.', 'cement.system-follow-up.', 'cement.system-audit-evidence.'],
            'profile.edit' => ['profile.', 'security.'],
            'notifications.index' => ['notifications.'],
            'system-settings.index' => ['system-settings.index'],
            'system-settings.public-appearance.edit' => ['system-settings.public-appearance.'],
            'system-settings.backups.index' => ['system-settings.backups.'],
            'cement.maintenance.index' => ['cement.maintenance.'],
            'cement.import.index' => ['cement.import.'],
            'cement.exports.index' => ['cement.exports.'],
            default => str_ends_with($routeName, '.index')
                ? [substr($routeName, 0, -6).'.']
                : [],
        };
    }

    private function isAlwaysAllowedRoute(string $routeName, Request $request): bool
    {
        return $routeName === 'dashboard'
            || $routeName === 'admin.dashboard'
            || $routeName === 'petugas.dashboard'
            || str_starts_with($routeName, 'livewire.')
            || str_starts_with($routeName, 'password.')
            || $routeName === 'logout'
            || $request->is(
                'settings/profile',
                'settings/security',
                'user/confirmed-password-status',
                'user/confirm-password*',
            );
    }

    private function cacheKey(string $role): string
    {
        return "navigation.items.{$role}.v2";
    }

    /**
     * @return array<int, string>
     */
    private function deprecatedRouteNames(): array
    {
        return ['security.edit'];
    }

    /**
     * @return list<string>
     */
    private function fullAccessOnlyRouteNames(): array
    {
        return [
            'admin.users.index',
            'system-settings.navigation.index',
        ];
    }
}
