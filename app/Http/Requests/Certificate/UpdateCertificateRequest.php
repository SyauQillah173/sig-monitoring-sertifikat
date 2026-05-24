<?php

namespace App\Http\Requests\Certificate;

use App\Enums\UserRole;
use App\Models\Certificate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCertificateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyAppRole([UserRole::Admin, UserRole::Petugas]) ?? false;
    }

    public function rules(): array
    {
        /** @var Certificate|null $certificate */
        $certificate = $this->route('certificate');

        return [
            'product_id' => ['required', 'integer', Rule::exists('products', 'id')],
            'certificate_type_id' => ['required', 'integer', Rule::exists('certificate_types', 'id')],
            'issuer_id' => ['required', 'integer', Rule::exists('issuers', 'id')],
            'certificate_number' => [
                'required',
                'string',
                'max:255',
                Rule::unique('certificates', 'certificate_number')->ignore($certificate),
            ],
            'issue_date' => ['required', 'date'],
            'expiry_date' => ['required', 'date', 'after_or_equal:issue_date'],
            'document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:'.config('filesystems.certificate_files.max_upload_kb', 5120)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'product_id' => 'produk',
            'certificate_type_id' => 'jenis sertifikat',
            'issuer_id' => 'lembaga penerbit',
            'certificate_number' => 'nomor sertifikat',
            'issue_date' => 'tanggal terbit',
            'expiry_date' => 'tanggal habis berlaku',
            'document' => 'scan sertifikat',
            'notes' => 'catatan',
        ];
    }
}
