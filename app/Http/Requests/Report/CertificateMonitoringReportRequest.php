<?php

namespace App\Http\Requests\Report;

use App\Enums\UserRole;
use App\Models\Certificate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CertificateMonitoringReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAppRole(UserRole::Admin) ?? false;
    }

    public function rules(): array
    {
        return [
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')],
            'product_id' => ['nullable', 'integer', Rule::exists('products', 'id')],
            'status' => ['nullable', 'string', Rule::in(array_keys(Certificate::monitoringFilterOptions()))],
        ];
    }

    /**
     * @return array{
     *     date_from: string|null,
     *     date_to: string|null,
     *     category_id: int|null,
     *     product_id: int|null,
     *     status: string
     * }
     */
    public function filters(): array
    {
        $validated = $this->validated();

        return [
            'date_from' => $validated['date_from'] ?? null,
            'date_to' => $validated['date_to'] ?? null,
            'category_id' => isset($validated['category_id']) ? (int) $validated['category_id'] : null,
            'product_id' => isset($validated['product_id']) ? (int) $validated['product_id'] : null,
            'status' => $validated['status'] ?? 'all',
        ];
    }

    public function attributes(): array
    {
        return [
            'date_from' => 'tanggal mulai',
            'date_to' => 'tanggal akhir',
            'category_id' => 'kategori',
            'product_id' => 'produk',
            'status' => 'status',
        ];
    }
}
