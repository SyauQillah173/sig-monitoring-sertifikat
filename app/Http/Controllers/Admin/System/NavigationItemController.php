<?php

namespace App\Http\Controllers\Admin\System;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\NavigationItem;
use App\Services\AuditLogger;
use App\Services\SystemNavigationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class NavigationItemController extends Controller
{
    public function __construct(
        private readonly SystemNavigationService $navigation,
    ) {}

    public function index(): View
    {
        $this->navigation->ensureDefaultsExist();

        return view('admin.system-settings.navigation.index', [
            'items' => NavigationItem::query()->ordered()->get(),
            'icons' => $this->navigation->availableIcons(),
            'roles' => UserRole::cases(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $items = $request->validate([
            'items' => ['required', 'array'],
            'items.*.id' => ['required', 'integer'],
            'items.*.group_label' => ['required', 'string', 'max:80'],
            'items.*.label' => ['required', 'string', 'max:80'],
            'items.*.icon' => ['required', 'string', Rule::in($this->navigation->availableIconNames())],
            'items.*.sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
            'items.*.allowed_roles' => ['nullable', 'array'],
            'items.*.allowed_roles.*' => ['required', Rule::in(UserRole::values())],
            'items.*.is_active' => ['nullable', 'boolean'],
        ])['items'];

        $ids = collect($items)
            ->pluck('id')
            ->map(fn (int|string $id): int => (int) $id)
            ->unique()
            ->values();

        $routeNamesById = NavigationItem::query()
            ->whereIn('id', $ids)
            ->pluck('route_name', 'id');

        if ($routeNamesById->count() !== $ids->count()) {
            throw ValidationException::withMessages([
                'items' => 'Sebagian menu tidak ditemukan. Muat ulang halaman lalu coba lagi.',
            ]);
        }

        $now = now();

        $rows = collect($items)
            ->map(fn (array $item): array => [
                'id' => (int) $item['id'],
                'group_label' => $item['group_label'],
                'label' => $item['label'],
                'route_name' => $routeNamesById[(int) $item['id']],
                'icon' => $item['icon'],
                'sort_order' => (int) $item['sort_order'],
                'allowed_roles' => json_encode(array_values($item['allowed_roles'] ?? [])),
                'is_active' => (bool) ($item['is_active'] ?? false),
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->all();

        NavigationItem::query()->upsert(
            $rows,
            ['id'],
            ['group_label', 'label', 'route_name', 'icon', 'sort_order', 'allowed_roles', 'is_active', 'updated_at'],
        );

        $this->navigation->clearCache();

        app(AuditLogger::class)->log('navigation_items_updated', null, 'Admin memperbarui menu aplikasi.', null, [
            'items' => count($items),
        ]);

        return redirect()
            ->route('system-settings.navigation.index')
            ->with('success', 'Menu aplikasi berhasil diperbarui.');
    }
}
