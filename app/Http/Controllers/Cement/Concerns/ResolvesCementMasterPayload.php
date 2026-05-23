<?php

namespace App\Http\Controllers\Cement\Concerns;

use App\Models\CementReferenceValue;
use App\Models\LokasiPabrik;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

trait ResolvesCementMasterPayload
{
    /**
     * @return array<string, mixed>
     */
    private function referenceSelectionRules(string $type, string $idField, string $textField): array
    {
        return [
            $idField => [
                'required_without:'.$textField,
                'nullable',
                'integer',
                Rule::exists('cement_reference_values', 'id')
                    ->where('type', $type)
                    ->where('is_active', true),
            ],
            $textField => [
                'required_without:'.$idField,
                'nullable',
                'string',
                'max:255',
                CementReferenceValue::activeNameRule($type),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function locationSelectionRules(): array
    {
        return [
            'lokasi_pabrik_id' => [
                'required_without:lokasi',
                'nullable',
                'integer',
                Rule::exists('lokasi_pabrik', 'id')->where('is_active', true),
            ],
            'lokasi' => ['required_without:lokasi_pabrik_id', 'nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function referencePayload(Request $request, string $type, string $idField, string $textField): array
    {
        $reference = $request->filled($idField)
            ? CementReferenceValue::query()->where('type', $type)->findOrFail($request->integer($idField))
            : CementReferenceValue::query()
                ->where('type', $type)
                ->where('name', $request->input($textField))
                ->firstOrFail();

        return [
            $idField => $reference->id,
            $textField => $reference->name,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function locationPayload(Request $request): array
    {
        $location = $request->filled('lokasi_pabrik_id')
            ? LokasiPabrik::query()->findOrFail($request->integer('lokasi_pabrik_id'))
            : LokasiPabrik::query()->where('nama_lokasi', $request->input('lokasi'))->firstOrFail();

        return [
            'lokasi_pabrik_id' => $location->id,
            'lokasi' => $location->nama_lokasi,
        ];
    }
}
