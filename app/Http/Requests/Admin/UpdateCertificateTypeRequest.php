<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use App\Models\CertificateType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCertificateTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAppRole(UserRole::Admin) ?? false;
    }

    public function rules(): array
    {
        /** @var CertificateType $certificateType */
        $certificateType = $this->route('certificateType');

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('certificate_types', 'name')->ignore($certificateType->id)],
            'slug' => ['required', 'string', 'max:255', Rule::unique('certificate_types', 'slug')->ignore($certificateType->id)],
            'description' => ['nullable', 'string'],
            'renewal_period_days' => ['nullable', 'integer', 'min:1', 'max:36500'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
