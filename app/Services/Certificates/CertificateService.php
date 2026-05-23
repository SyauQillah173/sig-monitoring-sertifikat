<?php

namespace App\Services\Certificates;

use App\Enums\CertificateStatus;
use App\Models\Certificate;
use App\Models\CertificateType;
use App\Models\Issuer;
use App\Models\Product;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class CertificateService
{
    /**
     * @return LengthAwarePaginator<int, Certificate>
     */
    public function paginateForIndex(?string $status, int $perPage = 10): LengthAwarePaginator
    {
        return Certificate::query()
            ->with(['product.category', 'certificateType', 'issuer'])
            ->filterMonitoringStatus($status)
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @return array{products: Collection<int, Product>, certificateTypes: Collection<int, CertificateType>, issuers: Collection<int, Issuer>}
     */
    public function formOptions(): array
    {
        return [
            'products' => Product::query()
                ->with('category')
                ->orderBy('name')
                ->get(),
            'certificateTypes' => CertificateType::query()
                ->orderBy('name')
                ->get(),
            'issuers' => Issuer::query()
                ->orderBy('name')
                ->get(),
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function create(array $validated, User $actor, ?UploadedFile $document = null): Certificate
    {
        $documentPath = $document?->store('certificates', 'local');

        try {
            return DB::transaction(function () use ($validated, $actor, $documentPath) {
                return Certificate::query()->create(
                    $this->payload($validated, $actor->id, $actor->id, $documentPath),
                );
            });
        } catch (Throwable $throwable) {
            if ($documentPath) {
                $this->deleteDocument($documentPath);
            }

            throw $throwable;
        }
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function update(Certificate $certificate, array $validated, User $actor, ?UploadedFile $document = null): Certificate
    {
        $oldDocumentPath = $certificate->file_path;
        $newDocumentPath = $document?->store('certificates', 'local');

        try {
            DB::transaction(function () use ($certificate, $validated, $actor, $newDocumentPath) {
                $certificate->update(
                    $this->payload(
                        $validated,
                        $certificate->issued_by_user_id,
                        $actor->id,
                        $newDocumentPath ?: $certificate->file_path,
                    ),
                );
            });

            if ($newDocumentPath && $oldDocumentPath && $oldDocumentPath !== $newDocumentPath) {
                $this->deleteDocument($oldDocumentPath);
            }

            return $certificate->refresh();
        } catch (Throwable $throwable) {
            if ($newDocumentPath) {
                $this->deleteDocument($newDocumentPath);
            }

            throw $throwable;
        }
    }

    public function delete(Certificate $certificate): void
    {
        $documentPath = $certificate->file_path;

        $certificate->delete();

        if ($documentPath) {
            $this->deleteDocument($documentPath);
        }
    }

    public function documentExists(Certificate $certificate): bool
    {
        return $certificate->hasDocument()
            && (Storage::disk('local')->exists($certificate->file_path)
                || Storage::disk('public')->exists($certificate->file_path));
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function payload(array $validated, ?int $issuedByUserId, int $updatedByUserId, ?string $documentPath): array
    {
        return [
            'product_id' => $validated['product_id'],
            'certificate_type_id' => $validated['certificate_type_id'],
            'issuer_id' => $validated['issuer_id'],
            'certificate_number' => $validated['certificate_number'],
            'issued_at' => $validated['issue_date'],
            'expires_at' => $validated['expiry_date'],
            'file_path' => $documentPath,
            'notes' => $validated['notes'] ?? null,
            'issued_by_user_id' => $issuedByUserId,
            'updated_by_user_id' => $updatedByUserId,
            'status' => CertificateStatus::Active,
        ];
    }

    private function deleteDocument(string $path): void
    {
        Storage::disk('local')->delete($path);
        Storage::disk('public')->delete($path);
    }
}
