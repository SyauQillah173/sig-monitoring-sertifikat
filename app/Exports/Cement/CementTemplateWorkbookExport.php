<?php

namespace App\Exports\Cement;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class CementTemplateWorkbookExport implements WithMultipleSheets
{
    public function __construct(
        private readonly array $brands,
        private readonly array $locations,
        private readonly array $references,
    ) {}

    public function sheets(): array
    {
        return [
            new CementTemplateSheetExport('Sertifikat SNI', 'sni', $this->brands, $this->locations, $this->references),
            new CementTemplateSheetExport('Sertifikat TKDN', 'tkdn', $this->brands, $this->locations, $this->references),
            new CementTemplateSheetExport('Sertifikat Green Label', 'green_label', $this->brands, $this->locations, $this->references),
            new CementMasterSheetExport('MASTER_MEREK', ['id', 'kategori_id', 'kategori', 'merek'], $this->brands, '5B7CFA'),
            new CementMasterSheetExport('MASTER_LOKASI', ['id', 'lokasi', 'kode', 'status'], $this->locations, '0EA5E9'),
            new CementMasterSheetExport('MASTER_SNI', ['id', 'tipe', 'nama', 'kode'], $this->references['sni'] ?? [], 'EF4444'),
            new CementMasterSheetExport('MASTER_KOMODITI', ['id', 'tipe', 'nama', 'kode'], $this->references['komoditi'] ?? [], 'F97316'),
            new CementMasterSheetExport('MASTER_LSPRO', ['id', 'tipe', 'nama', 'kode'], $this->references['lspro'] ?? [], '14B8A6'),
            new CementMasterSheetExport('MASTER_JENIS_SERTIFIKASI', ['id', 'tipe', 'nama', 'kode'], $this->references['jenis_sertifikasi'] ?? [], '8B5CF6'),
            new CementMasterSheetExport('MASTER_KEMASAN', ['id', 'tipe', 'nama', 'kode'], $this->references['kemasan'] ?? [], '22C55E'),
            new CementMasterSheetExport('MASTER_PERINGKAT_GL', ['id', 'tipe', 'nama', 'kode'], $this->references['peringkat_green_label'] ?? [], 'EC4899'),
            new CementMasterSheetExport('PANDUAN', ['bagian', 'keterangan'], $this->guideRows(), '111827'),
        ];
    }

    private function guideRows(): array
    {
        return [
            ['Cara isi', 'Boleh pilih dari dropdown atau ketik manual di sheet Sertifikat SNI, Sertifikat TKDN, dan Sertifikat Green Label. Yang penting ejaan nilainya cocok dengan master/contoh.'],
            ['Baris contoh', 'Baris 2 dan 3 di setiap sheet sertifikat berisi contoh dari master database. Ganti dengan data asli atau hapus baris contoh sebelum import jika tidak ingin ikut disimpan.'],
            ['Kolom wajib SNI', 'kategori, merek, sni, komoditi, jenis_sertifikasi, lspro, lokasi, berlaku_sd.'],
            ['Kolom wajib TKDN', 'kategori, merek, sni, komoditi, persentase_tkdn, kemasan, lokasi, berlaku_sd.'],
            ['Kolom wajib Green Label', 'kategori, merek, sni, komoditi, peringkat, lokasi, berlaku_sd.'],
            ['Kolom tidak perlu diisi', 'sisa_hari_otomatis dan status_otomatis hanya rumus bantu di Excel. Sistem tetap menghitung status sendiri.'],
            ['ID otomatis', 'Saat import berhasil, sistem otomatis membuat ID sertifikat. Master kategori, merek, lokasi, SNI, komoditi, LSPro, kemasan, dan peringkat harus sudah ada.'],
            ['Sheet master', 'Sheet MASTER_* menjadi sumber dropdown dan acuan ejaan. Jika pilihan belum ada, tambahkan dulu lewat menu Pemeliharaan Data sebelum import.'],
            ['Tanggal', 'Template bawaan menyediakan validasi tanggal Excel pada kolom berlaku_sd. Format paling aman: yyyy-mm-dd, contoh 2029-06-16.'],
            ['Template perusahaan', 'File dari perusahaan tetap bisa di-upload jika kolomnya setara dan nilai datanya cocok dengan master database. User boleh mengetik manual; typo tetap ditolak saat Preview Data. Header boleh pakai spasi/kapital seperti Berlaku SD, File Sertifikat, atau Jenis Sertifikasi.'],
            ['File sertifikat', 'Opsional dan sebaiknya dikosongkan saat import. Excel tidak meng-upload PDF/JPG/PNG. Setelah data tersimpan, upload file asli dari menu Edit Sertifikat.'],
            ['Path file lama', 'Kolom file_sertifikat hanya boleh diisi jika path file sudah benar-benar ada di storage aplikasi, misalnya uploads/sertifikat/nama-file.pdf.'],
            ['Simpan ke web', 'Upload file ini di menu Import Excel, klik Preview Data, periksa error, lalu klik Simpan Semua.'],
        ];
    }
}
