<?php

namespace App\Http\Controllers\Cement;

use App\Http\Controllers\Controller;
use App\Models\NotificationSetting;
use App\Services\AuditLogger;
use App\Services\CertificateFileStorage;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CertificateTemplateController extends Controller
{
    private const SETTING_KEY = 'certificate_template_path';

    private const DEFAULT_TEMPLATE = 'images/Sertifikat.optimized.jpg';

    public function __construct(
        private readonly CertificateFileStorage $files,
    ) {}

    public function edit(): View
    {
        $templatePath = $this->templatePath();

        return view('cement.maintenance.certificate-template.edit', [
            'templatePath' => $templatePath,
            'templatePreviewSrc' => $this->templatePreviewSource($templatePath),
            'defaultTemplate' => self::DEFAULT_TEMPLATE,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'template' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:12288'],
        ]);

        $file = $validated['template'];
        $filename = 'sertifikat-template-'.now()->format('YmdHis').'-'.Str::random(8).'.jpg';
        $relativePath = 'images/certificate-templates/'.$filename;
        $optimizedImage = $this->optimizeTemplateImage((string) file_get_contents($file->getRealPath()));

        if ($optimizedImage === null) {
            return back()
                ->withInput()
                ->with('error', 'Template gagal diproses. Gunakan file JPG, PNG, atau WEBP yang valid.');
        }

        $this->files->put($relativePath, $optimizedImage, 'image/jpeg', $file->getClientOriginalName());

        $oldPath = NotificationSetting::query()->where('key', self::SETTING_KEY)->value('value');

        NotificationSetting::query()->updateOrCreate(
            ['key' => self::SETTING_KEY],
            [
                'value' => $relativePath,
                'description' => 'Template background dokumen ringkasan sertifikat.',
            ],
        );

        if ($oldPath && $oldPath !== self::DEFAULT_TEMPLATE) {
            $this->files->delete($oldPath);
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
            $this->files->delete($oldPath);
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
        $path = $path === 'images/Sertifikat.jpg' ? self::DEFAULT_TEMPLATE : $path;

        return $this->files->exists($path) || file_exists(public_path($path)) ? $path : self::DEFAULT_TEMPLATE;
    }

    private function templatePreviewSource(string $path): string
    {
        return $this->files->dataUri($path) ?: asset($path);
    }

    private function optimizeTemplateImage(string $contents): ?string
    {
        $source = @imagecreatefromstring($contents);

        if (! $source) {
            return null;
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $maxWidth = 1240;
        $maxHeight = 1754;
        $ratio = min($maxWidth / $width, $maxHeight / $height, 1);
        $newWidth = max(1, (int) round($width * $ratio));
        $newHeight = max(1, (int) round($height * $ratio));

        $canvas = imagecreatetruecolor($newWidth, $newHeight);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefill($canvas, 0, 0, $white);
        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        imagedestroy($source);

        foreach ([72, 64, 56] as $quality) {
            ob_start();
            imagejpeg($canvas, null, $quality);
            $optimized = (string) ob_get_clean();

            if (strlen($optimized) <= 700 * 1024 || $quality === 56) {
                imagedestroy($canvas);

                return $optimized;
            }
        }

        imagedestroy($canvas);

        return null;
    }
}
