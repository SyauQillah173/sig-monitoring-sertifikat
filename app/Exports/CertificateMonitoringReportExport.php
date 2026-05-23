<?php

namespace App\Exports;

use App\Models\Certificate;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CertificateMonitoringReportExport implements FromCollection, ShouldAutoSize, WithHeadings
{
    /**
     * @param  Collection<int, Certificate>  $certificates
     */
    public function __construct(
        private readonly Collection $certificates,
    ) {}

    public function headings(): array
    {
        return [
            'Nomor Sertifikat',
            'Produk',
            'Kategori',
            'Jenis Sertifikat',
            'Lembaga Penerbit',
            'Tanggal Terbit',
            'Tanggal Habis Berlaku',
            'Status',
            'Catatan',
        ];
    }

    public function collection(): Collection
    {
        return $this->certificates->map(fn (Certificate $certificate) => [
            'certificate_number' => $certificate->certificate_number,
            'product_name' => $certificate->product->name,
            'category_name' => $certificate->product->category?->name,
            'certificate_type' => $certificate->certificateType->name,
            'issuer_name' => $certificate->issuer->name,
            'issued_at' => $certificate->issued_at->format('Y-m-d'),
            'expires_at' => $certificate->expires_at->format('Y-m-d'),
            'status' => $certificate->statusLabel(),
            'notes' => $certificate->notes,
        ]);
    }
}
