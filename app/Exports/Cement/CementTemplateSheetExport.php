<?php

namespace App\Exports\Cement;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CementTemplateSheetExport implements FromArray, ShouldAutoSize, WithEvents, WithHeadings, WithTitle
{
    private const DATA_ROWS = 120;

    public function __construct(
        private readonly string $title,
        private readonly string $kind,
        private readonly array $brands = [],
        private readonly array $locations = [],
        private readonly array $references = [],
    ) {}

    public function title(): string
    {
        return $this->title;
    }

    public function headings(): array
    {
        return match ($this->kind) {
            'tkdn' => ['kategori', 'merek', 'sni', 'komoditi', 'persentase_tkdn', 'kemasan', 'lokasi', 'berlaku_sd', 'file_sertifikat', 'sisa_hari_otomatis', 'status_otomatis'],
            'green_label' => ['kategori', 'merek', 'sni', 'komoditi', 'peringkat', 'lokasi', 'berlaku_sd', 'file_sertifikat', 'sisa_hari_otomatis', 'status_otomatis'],
            default => ['kategori', 'merek', 'sni', 'komoditi', 'jenis_sertifikasi', 'lspro', 'lokasi', 'berlaku_sd', 'file_sertifikat', 'sisa_hari_otomatis', 'status_otomatis'],
        };
    }

    public function array(): array
    {
        $rows = [];
        $sampleRows = $this->sampleRows();

        for ($row = 2; $row <= self::DATA_ROWS + 1; $row++) {
            $rows[] = $this->rowWithFormulas($row, $sampleRows[$row - 2] ?? []);
        }

        return $rows;
    }

    private function rowWithFormulas(int $row, array $values = []): array
    {
        return match ($this->kind) {
            'tkdn' => [
                $values['kategori'] ?? '',
                $values['merek'] ?? '',
                $values['sni'] ?? '',
                $values['komoditi'] ?? '',
                $values['persentase_tkdn'] ?? '',
                $values['kemasan'] ?? '',
                $values['lokasi'] ?? '',
                $values['berlaku_sd'] ?? '',
                $values['file_sertifikat'] ?? '',
                '=IFERROR(IF($H'.$row.'="","",$H'.$row.'-TODAY()),"CEK FORMAT TANGGAL")',
                '=IF($H'.$row.'="","",IFERROR(IF($H'.$row.'-TODAY()<0,"KEDALUWARSA","AKAN DIBACA SISTEM"),"CEK FORMAT TANGGAL"))',
            ],
            'green_label' => [
                $values['kategori'] ?? '',
                $values['merek'] ?? '',
                $values['sni'] ?? '',
                $values['komoditi'] ?? '',
                $values['peringkat'] ?? '',
                $values['lokasi'] ?? '',
                $values['berlaku_sd'] ?? '',
                $values['file_sertifikat'] ?? '',
                '=IFERROR(IF($G'.$row.'="","",$G'.$row.'-TODAY()),"CEK FORMAT TANGGAL")',
                '=IF($G'.$row.'="","",IFERROR(IF($G'.$row.'-TODAY()<0,"KEDALUWARSA","AKAN DIBACA SISTEM"),"CEK FORMAT TANGGAL"))',
            ],
            default => [
                $values['kategori'] ?? '',
                $values['merek'] ?? '',
                $values['sni'] ?? '',
                $values['komoditi'] ?? '',
                $values['jenis_sertifikasi'] ?? '',
                $values['lspro'] ?? '',
                $values['lokasi'] ?? '',
                $values['berlaku_sd'] ?? '',
                $values['file_sertifikat'] ?? '',
                '=IFERROR(IF($H'.$row.'="","",$H'.$row.'-TODAY()),"CEK FORMAT TANGGAL")',
                '=IF($H'.$row.'="","",IFERROR(IF($H'.$row.'-TODAY()<0,"KEDALUWARSA","AKAN DIBACA SISTEM"),"CEK FORMAT TANGGAL"))',
            ],
        };
    }

    private function sampleRows(): array
    {
        if ($this->brands === [] || $this->locations === []) {
            return [];
        }

        return match ($this->kind) {
            'tkdn' => array_filter([
                $this->sampleTkdnRow(0, '2029-06-16', '42,5'),
                $this->sampleTkdnRow(1, '2030-01-01', '55'),
            ]),
            'green_label' => array_filter([
                $this->sampleGreenLabelRow(0, '2029-06-16'),
                $this->sampleGreenLabelRow(1, '2030-01-01'),
            ]),
            default => array_filter([
                $this->sampleSniRow(0, '2029-06-16'),
                $this->sampleSniRow(1, '2030-01-01'),
            ]),
        };
    }

    private function sampleSniRow(int $index, string $date): ?array
    {
        $brand = $this->sampleBrand($index);
        $location = $this->sampleLocation($index);

        if (! $brand || ! $location) {
            return null;
        }

        return [
            'kategori' => $brand[2] ?? '',
            'merek' => $brand[3] ?? '',
            'sni' => $this->sampleReference('sni', $index),
            'komoditi' => $this->sampleReference('komoditi', $index),
            'jenis_sertifikasi' => $this->sampleReference('jenis_sertifikasi', $index),
            'lspro' => $this->sampleReference('lspro', $index),
            'lokasi' => $location[1] ?? '',
            'berlaku_sd' => $date,
        ];
    }

    private function sampleTkdnRow(int $index, string $date, string $percentage): ?array
    {
        $brand = $this->sampleBrand($index);
        $location = $this->sampleLocation($index);

        if (! $brand || ! $location) {
            return null;
        }

        return [
            'kategori' => $brand[2] ?? '',
            'merek' => $brand[3] ?? '',
            'sni' => $this->sampleReference('sni', $index),
            'komoditi' => $this->sampleReference('komoditi', $index),
            'persentase_tkdn' => $percentage,
            'kemasan' => $this->sampleReference('kemasan', $index),
            'lokasi' => $location[1] ?? '',
            'berlaku_sd' => $date,
        ];
    }

    private function sampleGreenLabelRow(int $index, string $date): ?array
    {
        $brand = $this->sampleBrand($index);
        $location = $this->sampleLocation($index);

        if (! $brand || ! $location) {
            return null;
        }

        return [
            'kategori' => $brand[2] ?? '',
            'merek' => $brand[3] ?? '',
            'sni' => $this->sampleReference('sni', $index),
            'komoditi' => $this->sampleReference('komoditi', $index),
            'peringkat' => $this->sampleReference('peringkat_green_label', $index),
            'lokasi' => $location[1] ?? '',
            'berlaku_sd' => $date,
        ];
    }

    private function sampleBrand(int $index): ?array
    {
        return $this->brands[$index] ?? $this->brands[0] ?? null;
    }

    private function sampleLocation(int $index): ?array
    {
        return $this->locations[$index] ?? $this->locations[0] ?? null;
    }

    private function sampleReference(string $type, int $index): string
    {
        return $this->references[$type][$index][2] ?? $this->references[$type][0][2] ?? '';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $this->styleSheet($event->sheet->getDelegate());
            },
        ];
    }

    private function styleSheet(Worksheet $sheet): void
    {
        $lastColumn = $sheet->getHighestColumn();
        $lastRow = self::DATA_ROWS + 1;

        $sheet->getTabColor()->setRGB($this->accentColor());
        $sheet->freezePane('A2');
        $sheet->setAutoFilter("A1:{$lastColumn}{$lastRow}");
        $sheet->getRowDimension(1)->setRowHeight(28);

        $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $this->accentColor()]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        $sheet->getStyle("A1:{$lastColumn}{$lastRow}")->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D7DDF3']]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        ]);

        $sheet->getStyle("A2:{$lastColumn}{$lastRow}")->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F3F6FF']],
        ]);

        foreach ($this->inputColumns() as $column) {
            $sheet->getStyle("{$column}2:{$column}{$lastRow}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFF7D6']],
            ]);
        }

        foreach ($this->optionalColumns() as $column) {
            $sheet->getStyle("{$column}2:{$column}{$lastRow}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EAF7EF']],
            ]);
        }

        foreach ($this->dateColumns() as $column) {
            $sheet->getStyle("{$column}2:{$column}{$lastRow}")
                ->getNumberFormat()
                ->setFormatCode('yyyy-mm-dd');

            $this->applyDateValidation($sheet, $column, $lastRow);
        }

        foreach ($this->masterDropdowns() as $column => $formula) {
            $this->applyListValidation($sheet, $column, $lastRow, $formula);
        }

        foreach ($this->statusColumns() as $column) {
            $sheet->getStyle("{$column}2:{$column}{$lastRow}")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => '0F766E']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);
        }

        foreach ($this->headingNotes() as $cell => $note) {
            $sheet->getComment($cell)->getText()->createTextRun($note);
        }

        $sheet->getStyle("A2:{$lastColumn}{$lastRow}")
            ->getAlignment()
            ->setVertical(Alignment::VERTICAL_CENTER);
    }

    private function applyDateValidation(Worksheet $sheet, string $column, int $lastRow): void
    {
        $validation = new DataValidation;
        $validation->setType(DataValidation::TYPE_DATE);
        $validation->setErrorStyle(DataValidation::STYLE_STOP);
        $validation->setAllowBlank(false);
        $validation->setShowInputMessage(true);
        $validation->setShowErrorMessage(true);
        $validation->setOperator(DataValidation::OPERATOR_BETWEEN);
        $validation->setFormula1('DATE(1900,1,1)');
        $validation->setFormula2('DATE(2099,12,31)');
        $validation->setPromptTitle('Tanggal berlaku');
        $validation->setPrompt('Pilih/isi tanggal dengan format yyyy-mm-dd. Contoh: 2029-06-16.');
        $validation->setErrorTitle('Format tanggal belum valid');
        $validation->setError('Gunakan tanggal Excel yang valid, contoh 2029-06-16.');

        for ($row = 2; $row <= $lastRow; $row++) {
            $sheet->getCell("{$column}{$row}")->setDataValidation(clone $validation);
        }
    }

    private function applyListValidation(Worksheet $sheet, string $column, int $lastRow, string $formula): void
    {
        $validation = new DataValidation;
        $validation->setType(DataValidation::TYPE_LIST);
        $validation->setErrorStyle(DataValidation::STYLE_WARNING);
        $validation->setAllowBlank(false);
        $validation->setShowDropDown(true);
        $validation->setShowInputMessage(true);
        $validation->setShowErrorMessage(true);
        $validation->setFormula1($formula);
        $validation->setPromptTitle('Dropdown atau ketik manual');
        $validation->setPrompt('Boleh pilih dari dropdown atau ketik manual, asalkan ejaannya sama dengan master/contoh.');
        $validation->setErrorTitle('Cek ejaan data');
        $validation->setError('Nilai ini tidak persis ada di dropdown. Boleh dilanjutkan jika memang sudah sesuai master; web akan menolak saat Preview Data jika typo.');

        for ($row = 2; $row <= $lastRow; $row++) {
            $sheet->getCell("{$column}{$row}")->setDataValidation(clone $validation);
        }
    }

    private function masterDropdowns(): array
    {
        return array_filter(match ($this->kind) {
            'tkdn' => [
                'A' => $this->masterRange('MASTER_MEREK', 'C', count($this->brands)),
                'B' => $this->masterRange('MASTER_MEREK', 'D', count($this->brands)),
                'C' => $this->referenceRange('sni'),
                'D' => $this->referenceRange('komoditi'),
                'F' => $this->referenceRange('kemasan'),
                'G' => $this->masterRange('MASTER_LOKASI', 'B', count($this->locations)),
            ],
            'green_label' => [
                'A' => $this->masterRange('MASTER_MEREK', 'C', count($this->brands)),
                'B' => $this->masterRange('MASTER_MEREK', 'D', count($this->brands)),
                'C' => $this->referenceRange('sni'),
                'D' => $this->referenceRange('komoditi'),
                'E' => $this->referenceRange('peringkat_green_label'),
                'F' => $this->masterRange('MASTER_LOKASI', 'B', count($this->locations)),
            ],
            default => [
                'A' => $this->masterRange('MASTER_MEREK', 'C', count($this->brands)),
                'B' => $this->masterRange('MASTER_MEREK', 'D', count($this->brands)),
                'C' => $this->referenceRange('sni'),
                'D' => $this->referenceRange('komoditi'),
                'E' => $this->referenceRange('jenis_sertifikasi'),
                'F' => $this->referenceRange('lspro'),
                'G' => $this->masterRange('MASTER_LOKASI', 'B', count($this->locations)),
            ],
        });
    }

    private function referenceRange(string $type): ?string
    {
        $sheet = match ($type) {
            'sni' => 'MASTER_SNI',
            'komoditi' => 'MASTER_KOMODITI',
            'lspro' => 'MASTER_LSPRO',
            'jenis_sertifikasi' => 'MASTER_JENIS_SERTIFIKASI',
            'kemasan' => 'MASTER_KEMASAN',
            'peringkat_green_label' => 'MASTER_PERINGKAT_GL',
            default => null,
        };

        if (! $sheet) {
            return null;
        }

        return $this->masterRange($sheet, 'C', count($this->references[$type] ?? []));
    }

    private function masterRange(string $sheet, string $column, int $rowCount): ?string
    {
        if ($rowCount < 1) {
            return null;
        }

        $lastRow = $rowCount + 1;

        return "'".$sheet."'!$".$column.'$2:$'.$column.'$'.$lastRow;
    }

    private function inputColumns(): array
    {
        return match ($this->kind) {
            'tkdn' => ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I'],
            'green_label' => ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'],
            default => ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I'],
        };
    }

    private function optionalColumns(): array
    {
        return match ($this->kind) {
            'green_label' => ['H'],
            default => ['I'],
        };
    }

    private function dateColumns(): array
    {
        return match ($this->kind) {
            'green_label' => ['G'],
            default => ['H'],
        };
    }

    private function statusColumns(): array
    {
        return match ($this->kind) {
            'green_label' => ['I', 'J'],
            default => ['J', 'K'],
        };
    }

    private function headingNotes(): array
    {
        $base = [
            'A1' => 'Wajib diisi. Boleh ketik manual, tetapi ejaan harus sama dengan master/contoh.',
            'B1' => 'Wajib diisi. Boleh ketik manual, tetapi ejaan harus sama dengan master/contoh.',
            'C1' => 'Wajib diisi. Boleh ketik manual, tetapi ejaan harus sama dengan master/contoh.',
            'D1' => 'Wajib diisi. Boleh ketik manual, tetapi ejaan harus sama dengan master/contoh.',
        ];

        return match ($this->kind) {
            'tkdn' => $base + [
                'E1' => 'Wajib diisi angka, boleh pakai koma atau titik. Contoh: 42,5.',
                'F1' => 'Wajib diisi. Boleh ketik manual, tetapi ejaan harus sama dengan master/contoh.',
                'G1' => 'Wajib diisi. Boleh ketik manual, tetapi ejaan harus sama dengan master/contoh.',
                'H1' => 'Wajib diisi tanggal format yyyy-mm-dd. Contoh: 2029-06-16.',
                'I1' => 'Opsional. Kosongkan untuk import biasa. Upload file PDF/JPG/PNG lewat menu Edit Sertifikat setelah import.',
                'J1' => 'Otomatis, tidak perlu diisi.',
                'K1' => 'Otomatis, tidak perlu diisi.',
            ],
            'green_label' => $base + [
                'E1' => 'Wajib diisi. Boleh ketik manual, tetapi ejaan harus sama dengan master/contoh.',
                'F1' => 'Wajib diisi. Boleh ketik manual, tetapi ejaan harus sama dengan master/contoh.',
                'G1' => 'Wajib diisi tanggal format yyyy-mm-dd. Contoh: 2029-06-16.',
                'H1' => 'Opsional. Kosongkan untuk import biasa. Upload file PDF/JPG/PNG lewat menu Edit Sertifikat setelah import.',
                'I1' => 'Otomatis, tidak perlu diisi.',
                'J1' => 'Otomatis, tidak perlu diisi.',
            ],
            default => $base + [
                'E1' => 'Wajib diisi. Boleh ketik manual, tetapi ejaan harus sama dengan master/contoh.',
                'F1' => 'Wajib diisi. Boleh ketik manual, tetapi ejaan harus sama dengan master/contoh.',
                'G1' => 'Wajib diisi. Boleh ketik manual, tetapi ejaan harus sama dengan master/contoh.',
                'H1' => 'Wajib diisi tanggal format yyyy-mm-dd. Contoh: 2029-06-16.',
                'I1' => 'Opsional. Kosongkan untuk import biasa. Upload file PDF/JPG/PNG lewat menu Edit Sertifikat setelah import.',
                'J1' => 'Otomatis, tidak perlu diisi.',
                'K1' => 'Otomatis, tidak perlu diisi.',
            ],
        };
    }

    private function accentColor(): string
    {
        return match ($this->kind) {
            'tkdn' => '1D4ED8',
            'green_label' => '16A34A',
            default => 'DC2626',
        };
    }
}
