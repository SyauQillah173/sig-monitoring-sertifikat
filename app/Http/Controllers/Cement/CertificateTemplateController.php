<?php

namespace App\Http\Controllers\Cement;

use App\Http\Controllers\Controller;
use App\Models\NotificationSetting;
use App\Services\AuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class CertificateTemplateController extends Controller
{
    private const SETTING_KEY = 'certificate_template_path';

    private const DEFAULT_TEMPLATE = 'images/Sertifikat.jpg';

    public function edit(): View
    {
        return view('cement.maintenance.certificate-template.edit', [
            'templatePath' => $this->templatePath(),
            'defaultTemplate' => self::DEFAULT_TEMPLATE,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'template' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:12288'],
        ]);

        $file = $validated['template'];
        $extension = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $filename = 'sertifikat-template-'.now()->format('YmdHis').'-'.Str::random(8).'.'.$extension;
        $relativePath = 'images/certificate-templates/'.$filename;
        $targetDirectory = public_path('images/certificate-templates');

        File::ensureDirectoryExists($targetDirectory);
        $file->move($targetDirectory, $filename);

        $oldPath = NotificationSetting::query()->where('key', self::SETTING_KEY)->value('value');

        NotificationSetting::query()->updateOrCreate(
            ['key' => self::SETTING_KEY],
            [
                'value' => $relativePath,
                'description' => 'Template background dokumen ringkasan sertifikat.',
            ],
        );

        if ($oldPath && $oldPath !== self::DEFAULT_TEMPLATE) {
            File::delete(public_path($oldPath));
        }

        app(AuditLogger::class)->log('certificate_template_updated', null, 'Template dokumen sertifikat diperbarui.', null, [
            'template_path' => $relativePath,
        ]);

        return redirect()
            ->route('cement.maintenance.certificate-template.edit')
            ->with('success', 'Template sertifikat berhasil diupload. Dokumen baru akan otomatis memakai template ini.');
    }

    public function reset(): RedirectResponse
    {
        $oldPath = NotificationSetting::query()->where('key', self::SETTING_KEY)->value('value');

        NotificationSetting::query()->updateOrCreate(
            ['key' => self::SETTING_KEY],
            [
                'value' => self::DEFAULT_TEMPLATE,
                'description' => 'Template background dokumen ringkasan sertifikat.',
            ],
        );

        if ($oldPath && $oldPath !== self::DEFAULT_TEMPLATE) {
            File::delete(public_path($oldPath));
        }

        app(AuditLogger::class)->log('certificate_template_reset', null, 'Template dokumen sertifikat dikembalikan ke default.', null, [
            'template_path' => self::DEFAULT_TEMPLATE,
        ]);

        return redirect()
            ->route('cement.maintenance.certificate-template.edit')
            ->with('success', 'Template sertifikat berhasil dikembalikan ke default.');
    }

    private function templatePath(): string
    {
        $path = NotificationSetting::query()->where('key', self::SETTING_KEY)->value('value') ?: self::DEFAULT_TEMPLATE;

        return File::exists(public_path($path)) ? $path : self::DEFAULT_TEMPLATE;
    }
}
