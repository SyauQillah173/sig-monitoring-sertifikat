<?php

namespace App\Services;

use App\Models\StoredFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CertificateFileStorage
{
    public function store(?UploadedFile $file, string $directory): ?string
    {
        if (! $file) {
            return null;
        }

        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin');
        $path = trim($directory, '/').'/'.Str::uuid().'.'.$extension;

        if ($this->usesDatabase()) {
            return $this->put(
                $path,
                (string) file_get_contents($file->getRealPath()),
                $file->getClientMimeType() ?: 'application/octet-stream',
                $file->getClientOriginalName(),
            );
        }

        return $file->store($directory, 'local');
    }

    public function put(string $path, string $contents, ?string $mimeType = null, ?string $originalName = null): string
    {
        if ($this->usesDatabase()) {
            StoredFile::query()->updateOrCreate(
                ['path' => $path],
                [
                    'original_name' => $originalName,
                    'mime_type' => $mimeType,
                    'size' => strlen($contents),
                    'contents' => base64_encode($contents),
                ],
            );

            return $path;
        }

        Storage::disk('local')->put($path, $contents);

        return $path;
    }

    public function exists(?string $path): bool
    {
        if (blank($path)) {
            return false;
        }

        return $this->databaseFile($path) !== null
            || Storage::disk('local')->exists($path)
            || Storage::disk('public')->exists($path);
    }

    public function delete(?string $path): void
    {
        if (blank($path)) {
            return;
        }

        StoredFile::query()->where('path', $path)->delete();
        Storage::disk('local')->delete($path);
        Storage::disk('public')->delete($path);
    }

    public function download(string $path, ?string $filename = null): StreamedResponse
    {
        $storedFile = $this->databaseFile($path);

        if ($storedFile) {
            return response()->streamDownload(
                fn () => print($this->decodeContents($storedFile->contents)),
                $filename ?: basename($path),
                array_filter([
                    'Content-Type' => $storedFile->mime_type ?: 'application/octet-stream',
                    'Content-Length' => (string) $storedFile->size,
                ]),
            );
        }

        $disk = Storage::disk('local')->exists($path) ? 'local' : 'public';

        return Storage::disk($disk)->download($path, $filename);
    }

    public function dataUri(string $path): ?string
    {
        $storedFile = $this->databaseFile($path);

        if ($storedFile) {
            return 'data:'.($storedFile->mime_type ?: 'application/octet-stream').';base64,'.base64_encode($this->decodeContents($storedFile->contents));
        }

        foreach (['local', 'public'] as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                return 'data:'.(Storage::disk($disk)->mimeType($path) ?: 'application/octet-stream').';base64,'.base64_encode(Storage::disk($disk)->get($path));
            }
        }

        return null;
    }

    public function maxUploadKilobytes(): int
    {
        return (int) config('filesystems.certificate_files.max_upload_kb', 3072);
    }

    private function usesDatabase(): bool
    {
        return config('filesystems.certificate_files.driver') === 'database'
            && Schema::hasTable('stored_files');
    }

    private function databaseFile(?string $path): ?StoredFile
    {
        if (blank($path) || ! Schema::hasTable('stored_files')) {
            return null;
        }

        return StoredFile::query()->where('path', $path)->first();
    }

    private function decodeContents(string $contents): string
    {
        $decoded = base64_decode($contents, true);

        return $decoded === false ? $contents : $decoded;
    }
}
