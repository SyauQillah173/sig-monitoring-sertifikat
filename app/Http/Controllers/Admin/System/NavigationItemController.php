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
            'items.*.id' => ['required', 'integer', 'exists:navigation_items,id'],
            'items.*.group_label' => ['required', 'string', 'max:80'],
            'items.*.label' => ['required', 'string', 'max:80'],
            'items.*.icon' => ['required', 'string', Rule::in($this->navigation->availableIconNames())],
            'items.*.sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
            'items.*.allowed_roles' => ['nullable', 'array'],
            'items.*.allowed_roles.*' => ['required', Rule::in(UserRole::values())],
            'items.*.is_active' => ['nullable', 'boolean'],
        ])['items'];

        foreach ($items as $item) {
            NavigationItem::query()
                ->whereKey($item['id'])
                ->update([
                    'group_label' => $item['group_label'],
                    'label' => $item['label'],
                    'icon' => $item['icon'],
                    'sort_order' => $item['sort_order'],
                    'allowed_roles' => array_values($item['allowed_roles'] ?? []),
                    'is_active' => (bool) ($item['is_active'] ?? false),
                ]);
        }

        $this->navigation->clearCache();

        app(AuditLogger::class)->log('navigation_items_updated', null, 'Admin memperbarui menu aplikasi.', null, [
            'items' => count($items),
        ]);

        return redirect()
            ->route('system-settings.navigation.index')
            ->with('success', 'Menu aplikasi berhasil diperbarui.');
    }
}
