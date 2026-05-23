<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use App\Models\Issuer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateIssuerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAppRole(UserRole::Admin) ?? false;
    }

    public function rules(): array
    {
        /** @var Issuer $issuer */
        $issuer = $this->route('issuer');

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('issuers', 'name')->ignore($issuer->id)],
            'code' => ['nullable', 'string', 'max:50', Rule::unique('issuers', 'code')->ignore($issuer->id)],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'url', 'max:255'],
            'address' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
