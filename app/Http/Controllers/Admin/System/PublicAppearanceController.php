<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use App\Services\SystemSettingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PublicAppearanceController extends Controller
{
    public function __construct(
        private readonly SystemSettingService $settings,
    ) {}

    public function edit(): View
    {
        return view('admin.system-settings.public-appearance.edit', [
            'settings' => $this->settings->publicLandingSettings(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $payload = $request->validate([
            'public_brand_kicker' => ['required', 'string', 'max:120'],
            'public_brand_name' => ['required', 'string', 'max:120'],
            'landing_badge' => ['required', 'string', 'max:120'],
            'landing_title' => ['required', 'string', 'max:180'],
            'landing_description' => ['required', 'string', 'max:700'],
            'landing_value_1_title' => ['required', 'string', 'max:120'],
            'landing_value_1_body' => ['required', 'string', 'max:220'],
            'landing_value_2_title' => ['required', 'string', 'max:120'],
            'landing_value_2_body' => ['required', 'string', 'max:220'],
            'landing_value_3_title' => ['required', 'string', 'max:120'],
            'landing_value_3_body' => ['required', 'string', 'max:220'],
            'show_landing_summary_stats' => ['nullable', 'boolean'],
            'show_landing_status_monitoring' => ['nullable', 'boolean'],
            'show_landing_document_composition' => ['nullable', 'boolean'],
            'show_landing_certificate_mix' => ['nullable', 'boolean'],
            'show_landing_public_iso' => ['nullable', 'boolean'],
            'show_landing_priority_feed' => ['nullable', 'boolean'],
            'show_public_iso_location' => ['nullable', 'boolean'],
            'show_public_iso_issuer' => ['nullable', 'boolean'],
            'show_public_iso_scope' => ['nullable', 'boolean'],
            'show_public_iso_validity' => ['nullable', 'boolean'],
            'show_public_iso_status' => ['nullable', 'boolean'],
            'show_public_iso_level_year' => ['nullable', 'boolean'],
            'show_public_iso_category' => ['nullable', 'boolean'],
            'footer_text' => ['required', 'string', 'max:120'],
        ]);

        $this->settings->savePublicLandingSettings($payload);

        app(AuditLogger::class)->log('public_appearance_settings_updated', null, 'Admin memperbarui tampilan publik landing page.', null, $payload);

        return redirect()
            ->route('system-settings.public-appearance.edit')
            ->with('success', 'Tampilan publik berhasil diperbarui.');
    }
}
