<?php

namespace App\Http\Controllers\Cement;

use App\Http\Controllers\Controller;
use App\Imports\Cement\CementRawImport;
use App\Models\CementReferenceValue;
use App\Models\KategoriSemen;
use App\Models\LokasiPabrik;
use App\Models\MerekSemen;
use App\Models\SertifikatGreenLabel;
use App\Models\SertifikatSni;
use App\Models\SertifikatTkdn;
use App\Services\AuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Throwable;

class CementImportController extends Controller
{
    private const SESSION_KEY = 'cement_certificate_import_preview';

    public function index(Request $request): View
    {
        return view('cement.import.index', [
            'preview' => $this->previewForRequest($request),
        ]);
    }

    public function preview(Request $request): RedirectResponse
    {
        $request->validate([
            'file_excel' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ]);

        try {
            $sheets = Excel::toArray(new CementRawImport, $request->file('file_excel'));
            $preview = $this->parseSheets($sheets);
        } catch (Throwable $throwable) {
            report($throwable);

            return back()->with('error', 'File Excel tidak bisa dibaca. Pastikan format template sudah benar.');
        }

        $this->storePreviewForRequest($request, $preview);
        $this->auditImport('cement_certificate_import_previewed', $preview);

        return redirect()->route('cement.import.index')->with(
            $preview['errors'] === [] ? 'success' : 'error',
            $preview['errors'] === [] ? 'Preview import berhasil dibuat. Silakan cek lalu simpan semua.' : 'Ada data yang perlu diperbaiki sebelum disimpan.',
        );
    }

    public function store(Request $request): RedirectResponse
    {
        $preview = $this->previewForRequest($request);

        if (! $preview) {
            return redirect()->route('cement.import.index')->with('error', 'Belum ada preview import yang siap disimpan.');
        }

        if ($preview['errors'] !== []) {
            return redirect()->route('cement.import.index')->with('error', 'Perbaiki error import sebelum menyimpan data.');
        }

        $storedRows = 0;

        DB::transaction(function () use ($preview, &$storedRows) {
            foreach ($preview['sni'] as $row) {
                if ($this->certificateExists('sni', $row)) {
                    continue;
                }

                $brand = $this->brandFromRow($row);
                $location = $this->locationFromRow($row);
                $sni = $this->referenceFromRow(CementReferenceValue::TYPE_SNI, $row['sni'], $row);
                $komoditi = $this->referenceFromRow(CementReferenceValue::TYPE_KOMODITI, $row['komoditi'], $row);
                $jenisSertifikasi = $this->referenceFromRow(CementReferenceValue::TYPE_JENIS_SERTIFIKASI, $row['jenis_sertifikasi'], $row);
                $lspro = $this->referenceFromRow(CementReferenceValue::TYPE_LSPRO, $row['lspro'], $row);

                SertifikatSni::query()->create([
                    'merek_semen_id' => $brand->id,
                    'sni_reference_id' => $sni->id,
                    'sni' => $sni->name,
                    'komoditi_reference_id' => $komoditi->id,
                    'komoditi' => $komoditi->name,
                    'jenis_sertifikasi_reference_id' => $jenisSertifikasi->id,
                    'jenis_sertifikasi' => $jenisSertifikasi->name,
                    'lspro_reference_id' => $lspro->id,
                    'lspro' => $lspro->name,
                    'lokasi_pabrik_id' => $location->id,
                    'lokasi' => $location->nama_lokasi,
                    'berlaku_sd' => $row['berlaku_sd'],
                    'file_sertifikat' => $row['file_sertifikat'] ?: null,
                ]);
                $storedRows++;
            }

            foreach ($preview['tkdn'] as $row) {
                if ($this->certificateExists('tkdn', $row)) {
                    continue;
                }

                $brand = $this->brandFromRow($row);
                $location = $this->locationFromRow($row);
                $sni = $this->referenceFromRow(CementReferenceValue::TYPE_SNI, $row['sni'], $row);
                $komoditi = $this->referenceFromRow(CementReferenceValue::TYPE_KOMODITI, $row['komoditi'], $row);
                $kemasan = $this->referenceFromRow(CementReferenceValue::TYPE_KEMASAN, $row['kemasan'], $row);

                SertifikatTkdn::query()->create([
                    'merek_semen_id' => $brand->id,
                    'sni_reference_id' => $sni->id,
                    'sni' => $sni->name,
                    'komoditi_reference_id' => $komoditi->id,
                    'komoditi' => $komoditi->name,
                    'persentase_tkdn' => $row['persentase_tkdn'],
                    'kemasan_reference_id' => $kemasan->id,
                    'kemasan' => $kemasan->name,
                    'lokasi_pabrik_id' => $location->id,
                    'lokasi' => $location->nama_lokasi,
                    'berlaku_sd' => $row['berlaku_sd'],
                    'file_sertifikat' => $row['file_sertifikat'] ?: null,
                ]);
                $storedRows++;
            }

            foreach ($preview['green_label'] as $row) {
                if ($this->certificateExists('green_label', $row)) {
                    continue;
                }

                $brand = $this->brandFromRow($row);
                $location = $this->locationFromRow($row);
                $sni = $this->referenceFromRow(CementReferenceValue::TYPE_SNI, $row['sni'], $row);
                $komoditi = $this->referenceFromRow(CementReferenceValue::TYPE_KOMODITI, $row['komoditi'], $row);
                $peringkat = $this->referenceFromRow(CementReferenceValue::TYPE_PERINGKAT_GREEN_LABEL, $row['peringkat'], $row);

                SertifikatGreenLabel::query()->create([
                    'merek_semen_id' => $brand->id,
                    'sni_reference_id' => $sni->id,
                    'sni' => $sni->name,
                    'komoditi_reference_id' => $komoditi->id,
                    'komoditi' => $komoditi->name,
                    'peringkat_green_label_reference_id' => $peringkat->id,
                    'peringkat' => $peringkat->name,
                    'lokasi_pabrik_id' => $location->id,
                    'lokasi' => $location->nama_lokasi,
                    'berlaku_sd' => $row['berlaku_sd'],
                    'file_sertifikat' => $row['file_sertifikat'] ?: null,
                ]);
                $storedRows++;
            }
        });

        $this->auditImport('cement_certificate_import_stored', $preview);
        $this->forgetPreviewForRequest($request);

        return redirect()->route('cement.products.index')->with(
            'success',
            $storedRows > 0
                ? "Import selesai. {$storedRows} data baru berhasil disimpan, data duplikat dilewati."
                : 'Tidak ada data baru untuk disimpan. Semua data import sudah ada di sistem.',
        );
    }

    private function previewForRequest(Request $request): ?array
    {
        $previewId = $request->session()->get(self::SESSION_KEY);

        if (is_array($previewId)) {
            return $previewId;
        }

        if (! is_string($previewId) || $previewId === '') {
            return null;
        }

        return Cache::store('database')->get($this->previewCacheKey($previewId));
    }

    private function storePreviewForRequest(Request $request, array $preview): void
    {
        $previewId = (string) Str::uuid();

        $request->session()->put(self::SESSION_KEY, $previewId);
        Cache::store('database')->put($this->previewCacheKey($previewId), $preview, now()->addHours(2));
    }

    private function forgetPreviewForRequest(Request $request): void
    {
        $previewId = $request->session()->get(self::SESSION_KEY);

        $request->session()->forget(self::SESSION_KEY);

        if (is_string($previewId) && $previewId !== '') {
            Cache::store('database')->forget($this->previewCacheKey($previewId));
        }
    }

    private function previewCacheKey(string $previewId): string
    {
        return self::SESSION_KEY.':'.$previewId;
    }

    private function brandFromRow(array $row): MerekSemen
    {
        if (filled($row['merek_id'] ?? null)) {
            return MerekSemen::query()->with('kategoriSemen')->findOrFail($this->parseMasterId($row['merek_id']));
        }

        $brand = $this->brandFromText($row['kategori'], $row['merek']);

        if (! $brand) {
            throw new \RuntimeException('Kategori/merek import tidak cocok dengan master database.');
        }

        return $brand;
    }

    private function locationFromRow(array $row): LokasiPabrik
    {
        if (filled($row['lokasi_pabrik_id'] ?? null)) {
            return LokasiPabrik::query()
                ->where('is_active', true)
                ->findOrFail($this->parseMasterId($row['lokasi_pabrik_id']));
        }

        $location = $this->locationFromText($row['lokasi']);

        if (! $location) {
            throw new \RuntimeException('Lokasi import tidak cocok dengan master database.');
        }

        return $location;
    }

    private function referenceFromRow(string $type, string $name, array $row): CementReferenceValue
    {
        $idColumn = $this->referenceIdColumn($type);

        if ($idColumn && filled($rowId = $row[$idColumn] ?? null)) {
            return CementReferenceValue::query()
                ->where('type', $type)
                ->where('is_active', true)
                ->findOrFail($this->parseMasterId($rowId));
        }

        $reference = $this->referenceFromText($type, $name);

        if (! $reference) {
            throw new \RuntimeException('Referensi import tidak cocok dengan master database.');
        }

        return $reference;
    }

    private function parseSheets(array $sheets): array
    {
        $preview = [
            'sni' => $this->parseSheet($sheets[0] ?? [], ['kategori', 'merek', 'sni', 'komoditi', 'jenis_sertifikasi', 'lspro', 'lokasi', 'berlaku_sd', 'file_sertifikat'], 'Sertifikat SNI', 'sni'),
            'tkdn' => $this->parseSheet($sheets[1] ?? [], ['kategori', 'merek', 'sni', 'komoditi', 'persentase_tkdn', 'kemasan', 'lokasi', 'berlaku_sd', 'file_sertifikat'], 'Sertifikat TKDN', 'tkdn'),
            'green_label' => $this->parseSheet($sheets[2] ?? [], ['kategori', 'merek', 'sni', 'komoditi', 'peringkat', 'lokasi', 'berlaku_sd', 'file_sertifikat'], 'Sertifikat Green Label', 'green_label'),
            'errors' => [],
            'new_references' => [],
            'skipped_duplicates' => [],
        ];

        $preview['errors'] = collect(['sni', 'tkdn', 'green_label'])
            ->flatMap(fn (string $key) => $preview[$key]['errors'])
            ->values()
            ->all();
        $preview['sni'] = $preview['sni']['rows'];
        $preview['tkdn'] = $preview['tkdn']['rows'];
        $preview['green_label'] = $preview['green_label']['rows'];
        $preview['new_references'] = [];

        if ($preview['errors'] === []) {
            [$preview['sni'], $skippedSni] = $this->onlyNewCertificateRows('sni', $preview['sni'], 'Sertifikat SNI');
            [$preview['tkdn'], $skippedTkdn] = $this->onlyNewCertificateRows('tkdn', $preview['tkdn'], 'Sertifikat TKDN');
            [$preview['green_label'], $skippedGreenLabel] = $this->onlyNewCertificateRows('green_label', $preview['green_label'], 'Sertifikat Green Label');
            $preview['skipped_duplicates'] = [...$skippedSni, ...$skippedTkdn, ...$skippedGreenLabel];
        }

        return $preview;
    }

    private function auditImport(string $action, array $preview): void
    {
        app(AuditLogger::class)->log($action, null, 'Import data sertifikat semen diproses.', null, [
            'sni_rows' => count($preview['sni'] ?? []),
            'tkdn_rows' => count($preview['tkdn'] ?? []),
            'green_label_rows' => count($preview['green_label'] ?? []),
            'error_count' => count($preview['errors'] ?? []),
            'new_reference_count' => count($preview['new_references'] ?? []),
            'skipped_duplicate_count' => count($preview['skipped_duplicates'] ?? []),
        ]);
    }

    private function parseSheet(array $rows, array $requiredColumns, string $sheetName, string $kind): array
    {
        $headings = collect($rows[0] ?? [])->map(fn ($heading) => $this->normalizeHeading($heading))->all();
        $errors = [];

        if (in_array('merek_id', $headings, true)) {
            return $this->parseMasterIdSheet($rows, $headings, $sheetName, $kind);
        }

        foreach ($requiredColumns as $column) {
            if (! in_array($column, $headings, true)) {
                $errors[] = "{$sheetName}: kolom {$column} tidak ditemukan.";
            }
        }

        if ($errors !== []) {
            return ['rows' => [], 'errors' => $errors];
        }

        $parsedRows = [];

        foreach (array_slice($rows, 1) as $index => $row) {
            $assoc = [];
            foreach ($headings as $columnIndex => $heading) {
                $assoc[$heading] = trim((string) ($row[$columnIndex] ?? ''));
            }

            if (collect($requiredColumns)->every(fn (string $column) => blank($assoc[$column] ?? null))) {
                continue;
            }

            foreach ($requiredColumns as $column) {
                if ($column !== 'file_sertifikat' && blank($assoc[$column] ?? null)) {
                    $errors[] = "{$sheetName} baris ".($index + 2).": kolom {$column} wajib diisi.";
                }
            }

            if (isset($assoc['persentase_tkdn']) && ! is_numeric(str_replace(',', '.', $assoc['persentase_tkdn']))) {
                $errors[] = "{$sheetName} baris ".($index + 2).': persentase_tkdn harus angka.';
            }

            $this->validateImportedCertificatePath($assoc['file_sertifikat'] ?? '', $sheetName, $index + 2, $errors);

            $date = $this->normalizeDate($row[array_search('berlaku_sd', $headings, true)] ?? null);
            if (! $date) {
                $errors[] = "{$sheetName} baris ".($index + 2).': berlaku_sd harus tanggal valid.';
            }

            $this->canonicalizeTextMasters($assoc, $sheetName, $index + 2, $kind, $errors);

            $assoc['berlaku_sd'] = $date;
            if (isset($assoc['persentase_tkdn'])) {
                $assoc['persentase_tkdn'] = (float) str_replace(',', '.', $assoc['persentase_tkdn']);
            }

            $parsedRows[] = collect($assoc)->only($requiredColumns)->all();
        }

        return ['rows' => $parsedRows, 'errors' => $errors];
    }

    /**
     * @return array<int, array{type: string, label: string, name: string}>
     */
    private function newReferences(array $preview): array
    {
        $rows = collect([
            ...$preview['sni'],
            ...$preview['tkdn'],
            ...$preview['green_label'],
        ]);

        $newMasterRows = collect([
            ...$rows
                ->pluck('kategori')
                ->filter()
                ->unique()
                ->reject(fn (string $name) => KategoriSemen::query()->where('nama_kategori', $name)->exists())
                ->map(fn (string $name) => ['type' => 'kategori', 'label' => 'Kategori Semen', 'name' => $name])
                ->all(),
            ...$rows
                ->map(fn (array $row) => ['kategori' => $row['kategori'] ?? '', 'merek' => $row['merek'] ?? ''])
                ->filter(fn (array $row) => filled($row['kategori']) && filled($row['merek']))
                ->unique(fn (array $row) => $row['kategori'].'|'.$row['merek'])
                ->reject(fn (array $row) => MerekSemen::query()
                    ->where('nama_merek', $row['merek'])
                    ->whereHas('kategoriSemen', fn ($query) => $query->where('nama_kategori', $row['kategori']))
                    ->exists())
                ->map(fn (array $row) => ['type' => 'merek', 'label' => 'Merek Semen', 'name' => $row['kategori'].' - '.$row['merek']])
                ->all(),
            ...$rows
                ->pluck('lokasi')
                ->filter()
                ->unique()
                ->reject(fn (string $name) => LokasiPabrik::query()->where('nama_lokasi', $name)->exists())
                ->map(fn (string $name) => ['type' => 'lokasi', 'label' => 'Lokasi Pabrik', 'name' => $name])
                ->all(),
        ]);

        $pairs = collect([
            ...collect($preview['sni'])->flatMap(fn (array $row) => [
                [CementReferenceValue::TYPE_SNI, $row['sni']],
                [CementReferenceValue::TYPE_KOMODITI, $row['komoditi']],
                [CementReferenceValue::TYPE_JENIS_SERTIFIKASI, $row['jenis_sertifikasi']],
                [CementReferenceValue::TYPE_LSPRO, $row['lspro']],
            ])->all(),
            ...collect($preview['tkdn'])->flatMap(fn (array $row) => [
                [CementReferenceValue::TYPE_SNI, $row['sni']],
                [CementReferenceValue::TYPE_KOMODITI, $row['komoditi']],
                [CementReferenceValue::TYPE_KEMASAN, $row['kemasan']],
            ])->all(),
            ...collect($preview['green_label'])->flatMap(fn (array $row) => [
                [CementReferenceValue::TYPE_SNI, $row['sni']],
                [CementReferenceValue::TYPE_KOMODITI, $row['komoditi']],
                [CementReferenceValue::TYPE_PERINGKAT_GREEN_LABEL, $row['peringkat']],
            ])->all(),
        ])->unique(fn (array $pair) => $pair[0].'|'.$pair[1])->values();

        $newReferenceRows = $pairs
            ->reject(fn (array $pair) => CementReferenceValue::query()
                ->where('type', $pair[0])
                ->where('name', $pair[1])
                ->exists())
            ->map(fn (array $pair) => [
                'type' => $pair[0],
                'label' => CementReferenceValue::labelFor($pair[0]),
                'name' => $pair[1],
            ])
            ->values()
            ->all();

        return $newMasterRows
            ->merge($newReferenceRows)
            ->values()
            ->all();
    }

    /**
     * @return array{0: array<int, array>, 1: array<int, array{sheet: string, row: int, reason: string}>}
     */
    private function onlyNewCertificateRows(string $kind, array $rows, string $sheetName): array
    {
        $newRows = [];
        $skipped = [];
        $seen = [];

        foreach ($rows as $index => $row) {
            $fingerprint = $this->duplicateFingerprint($kind, $row);
            $rowNumber = $index + 2;

            if (isset($seen[$fingerprint])) {
                $skipped[] = [
                    'sheet' => $sheetName,
                    'row' => $rowNumber,
                    'reason' => 'duplikat dengan baris lain di file Excel',
                ];

                continue;
            }

            $seen[$fingerprint] = true;

            if ($this->certificateExists($kind, $row)) {
                $skipped[] = [
                    'sheet' => $sheetName,
                    'row' => $rowNumber,
                    'reason' => 'data sudah ada di sistem',
                ];

                continue;
            }

            $newRows[] = $row;
        }

        return [$newRows, $skipped];
    }

    private function certificateExists(string $kind, array $row): bool
    {
        $brand = $this->brandFromRow($row);
        $location = $this->locationFromRow($row);
        $sni = $this->referenceFromRow(CementReferenceValue::TYPE_SNI, $row['sni'], $row);
        $komoditi = $this->referenceFromRow(CementReferenceValue::TYPE_KOMODITI, $row['komoditi'], $row);

        if ($kind === 'tkdn') {
            $kemasan = $this->referenceFromRow(CementReferenceValue::TYPE_KEMASAN, $row['kemasan'], $row);

            return SertifikatTkdn::query()
                ->where('merek_semen_id', $brand->id)
                ->where('sni_reference_id', $sni->id)
                ->where('komoditi_reference_id', $komoditi->id)
                ->where('kemasan_reference_id', $kemasan->id)
                ->where('lokasi_pabrik_id', $location->id)
                ->where('persentase_tkdn', (float) $row['persentase_tkdn'])
                ->whereDate('berlaku_sd', $row['berlaku_sd'])
                ->exists();
        }

        if ($kind === 'green_label') {
            $peringkat = $this->referenceFromRow(CementReferenceValue::TYPE_PERINGKAT_GREEN_LABEL, $row['peringkat'], $row);

            return SertifikatGreenLabel::query()
                ->where('merek_semen_id', $brand->id)
                ->where('sni_reference_id', $sni->id)
                ->where('komoditi_reference_id', $komoditi->id)
                ->where('peringkat_green_label_reference_id', $peringkat->id)
                ->where('lokasi_pabrik_id', $location->id)
                ->whereDate('berlaku_sd', $row['berlaku_sd'])
                ->exists();
        }

        $jenisSertifikasi = $this->referenceFromRow(CementReferenceValue::TYPE_JENIS_SERTIFIKASI, $row['jenis_sertifikasi'], $row);
        $lspro = $this->referenceFromRow(CementReferenceValue::TYPE_LSPRO, $row['lspro'], $row);

        return SertifikatSni::query()
            ->where('merek_semen_id', $brand->id)
            ->where('sni_reference_id', $sni->id)
            ->where('komoditi_reference_id', $komoditi->id)
            ->where('jenis_sertifikasi_reference_id', $jenisSertifikasi->id)
            ->where('lspro_reference_id', $lspro->id)
            ->where('lokasi_pabrik_id', $location->id)
            ->whereDate('berlaku_sd', $row['berlaku_sd'])
            ->exists();
    }

    private function duplicateFingerprint(string $kind, array $row): string
    {
        $fields = match ($kind) {
            'tkdn' => ['kategori', 'merek', 'sni', 'komoditi', 'persentase_tkdn', 'kemasan', 'lokasi', 'berlaku_sd'],
            'green_label' => ['kategori', 'merek', 'sni', 'komoditi', 'peringkat', 'lokasi', 'berlaku_sd'],
            default => ['kategori', 'merek', 'sni', 'komoditi', 'jenis_sertifikasi', 'lspro', 'lokasi', 'berlaku_sd'],
        };

        $values = collect($fields)
            ->map(function (string $field) use ($row): string {
                $value = $row[$field] ?? '';

                if ($field === 'persentase_tkdn') {
                    return number_format((float) $value, 4, '.', '');
                }

                return $this->normalizeLookupText((string) $value);
            })
            ->all();

        return $kind.'|'.implode('|', $values);
    }

    private function parseMasterIdSheet(array $rows, array $headings, string $sheetName, string $kind): array
    {
        $requiredColumns = $this->masterIdRequiredColumns($kind);
        $errors = [];

        foreach ($requiredColumns as $column) {
            if (! in_array($column, $headings, true)) {
                $errors[] = "{$sheetName}: kolom {$column} tidak ditemukan.";
            }
        }

        if ($errors !== []) {
            return ['rows' => [], 'errors' => $errors];
        }

        $parsedRows = [];

        foreach (array_slice($rows, 1) as $index => $row) {
            $assoc = [];
            foreach ($headings as $columnIndex => $heading) {
                $assoc[$heading] = trim((string) ($row[$columnIndex] ?? ''));
            }

            if (collect($requiredColumns)->every(fn (string $column) => blank($assoc[$column] ?? null))) {
                continue;
            }

            foreach ($requiredColumns as $column) {
                if ($column !== 'file_sertifikat' && blank($assoc[$column] ?? null)) {
                    $errors[] = "{$sheetName} baris ".($index + 2).": kolom {$column} wajib diisi.";
                }
            }

            if (isset($assoc['persentase_tkdn']) && filled($assoc['persentase_tkdn']) && ! is_numeric(str_replace(',', '.', $assoc['persentase_tkdn']))) {
                $errors[] = "{$sheetName} baris ".($index + 2).': persentase_tkdn harus angka.';
            }

            $this->validateImportedCertificatePath($assoc['file_sertifikat'] ?? '', $sheetName, $index + 2, $errors);

            $date = $this->normalizeDate($row[array_search('berlaku_sd', $headings, true)] ?? null);
            if (! $date) {
                $errors[] = "{$sheetName} baris ".($index + 2).': berlaku_sd harus tanggal valid.';
            }

            $rowErrors = [];
            $brand = $this->masterBrand($assoc['merek_id'] ?? null);
            $location = $this->masterLocation($assoc['lokasi_pabrik_id'] ?? null);
            $sni = $this->masterReference(CementReferenceValue::TYPE_SNI, $assoc['sni_reference_id'] ?? null);
            $komoditi = $this->masterReference(CementReferenceValue::TYPE_KOMODITI, $assoc['komoditi_reference_id'] ?? null);

            if (! $brand) {
                $rowErrors[] = 'merek_id';
            }

            if (! $location) {
                $rowErrors[] = 'lokasi_pabrik_id';
            }

            if (! $sni) {
                $rowErrors[] = 'sni_reference_id';
            }

            if (! $komoditi) {
                $rowErrors[] = 'komoditi_reference_id';
            }

            $jenisSertifikasi = null;
            $lspro = null;
            $kemasan = null;
            $peringkat = null;

            if ($kind === 'sni') {
                $jenisSertifikasi = $this->masterReference(CementReferenceValue::TYPE_JENIS_SERTIFIKASI, $assoc['jenis_sertifikasi_reference_id'] ?? null);
                $lspro = $this->masterReference(CementReferenceValue::TYPE_LSPRO, $assoc['lspro_reference_id'] ?? null);

                if (! $jenisSertifikasi) {
                    $rowErrors[] = 'jenis_sertifikasi_reference_id';
                }

                if (! $lspro) {
                    $rowErrors[] = 'lspro_reference_id';
                }
            }

            if ($kind === 'tkdn') {
                $kemasan = $this->masterReference(CementReferenceValue::TYPE_KEMASAN, $assoc['kemasan_reference_id'] ?? null);

                if (! $kemasan) {
                    $rowErrors[] = 'kemasan_reference_id';
                }
            }

            if ($kind === 'green_label') {
                $peringkat = $this->masterReference(CementReferenceValue::TYPE_PERINGKAT_GREEN_LABEL, $assoc['peringkat_green_label_reference_id'] ?? null);

                if (! $peringkat) {
                    $rowErrors[] = 'peringkat_green_label_reference_id';
                }
            }

            foreach ($rowErrors as $column) {
                $errors[] = "{$sheetName} baris ".($index + 2).": {$column} tidak ditemukan atau tidak aktif di master database.";
            }

            $parsedRows[] = [
                'merek_id' => $this->parseMasterId($assoc['merek_id'] ?? null),
                'kategori' => $brand?->kategoriSemen?->nama_kategori ?? '',
                'merek' => $brand?->nama_merek ?? '',
                'sni_reference_id' => $this->parseMasterId($assoc['sni_reference_id'] ?? null),
                'sni' => $sni?->name ?? '',
                'komoditi_reference_id' => $this->parseMasterId($assoc['komoditi_reference_id'] ?? null),
                'komoditi' => $komoditi?->name ?? '',
                'jenis_sertifikasi_reference_id' => $this->parseMasterId($assoc['jenis_sertifikasi_reference_id'] ?? null),
                'jenis_sertifikasi' => $jenisSertifikasi?->name ?? '',
                'lspro_reference_id' => $this->parseMasterId($assoc['lspro_reference_id'] ?? null),
                'lspro' => $lspro?->name ?? '',
                'persentase_tkdn' => isset($assoc['persentase_tkdn']) ? (float) str_replace(',', '.', $assoc['persentase_tkdn']) : null,
                'kemasan_reference_id' => $this->parseMasterId($assoc['kemasan_reference_id'] ?? null),
                'kemasan' => $kemasan?->name ?? '',
                'peringkat_green_label_reference_id' => $this->parseMasterId($assoc['peringkat_green_label_reference_id'] ?? null),
                'peringkat' => $peringkat?->name ?? '',
                'lokasi_pabrik_id' => $this->parseMasterId($assoc['lokasi_pabrik_id'] ?? null),
                'lokasi' => $location?->nama_lokasi ?? '',
                'berlaku_sd' => $date,
                'file_sertifikat' => $assoc['file_sertifikat'] ?? '',
            ];
        }

        return ['rows' => $parsedRows, 'errors' => $errors];
    }

    private function normalizeHeading(mixed $heading): string
    {
        $normalized = trim(strtolower((string) $heading));
        $normalized = preg_replace('/[^a-z0-9]+/', '_', $normalized) ?? '';
        $normalized = trim($normalized, '_');

        return match ($normalized) {
            'berlaku_s_d', 'berlaku_sd_', 'masa_berlaku', 'tanggal_berlaku', 'expired_date', 'expiry_date' => 'berlaku_sd',
            'file', 'file_pdf', 'dokumen', 'dokumen_sertifikat', 'sertifikat_file', 'file_certificate' => 'file_sertifikat',
            'jenis', 'jenis_sertifikat', 'jenis_sertifikasi_sni' => 'jenis_sertifikasi',
            'ls_pro', 'lembaga_sertifikasi_produk' => 'lspro',
            'pabrik', 'lokasi_pabrik' => 'lokasi',
            'tkdn', 'persen_tkdn', 'persentase' => 'persentase_tkdn',
            'brand' => 'merek',
            'category' => 'kategori',
            default => $normalized,
        };
    }

    private function validateTextMasters(array $assoc, string $sheetName, int $rowNumber, string $kind, array &$errors): void
    {
        if (! $this->brandFromText($assoc['kategori'] ?? '', $assoc['merek'] ?? '')) {
            $errors[] = "{$sheetName} baris {$rowNumber}: kategori/merek tidak ditemukan di master database.";
        }

        if (! $this->locationFromText($assoc['lokasi'] ?? '')) {
            $errors[] = "{$sheetName} baris {$rowNumber}: lokasi tidak ditemukan atau tidak aktif di master database.";
        }

        foreach ($this->textReferenceColumns($kind) as $type => $column) {
            if (! $this->referenceFromText($type, $assoc[$column] ?? '')) {
                $errors[] = "{$sheetName} baris {$rowNumber}: {$column} tidak ditemukan atau tidak aktif di master database.";
            }
        }
    }

    private function canonicalizeTextMasters(array &$assoc, string $sheetName, int $rowNumber, string $kind, array &$errors): void
    {
        if (filled($assoc['kategori'] ?? null) && filled($assoc['merek'] ?? null)) {
            $brand = $this->brandFromText($assoc['kategori'], $assoc['merek']);

            if (! $brand) {
                $errors[] = "{$sheetName} baris {$rowNumber}: kategori/merek tidak cocok dengan master database. Pilih dari dropdown template atau tambahkan dulu di Pemeliharaan Data.";
            } else {
                $assoc['kategori'] = $brand->kategoriSemen?->nama_kategori ?? $assoc['kategori'];
                $assoc['merek'] = $brand->nama_merek;
            }
        }

        if (filled($assoc['lokasi'] ?? null)) {
            $location = $this->locationFromText($assoc['lokasi']);

            if (! $location) {
                $errors[] = "{$sheetName} baris {$rowNumber}: lokasi tidak cocok dengan master database aktif. Pilih dari dropdown template atau tambahkan dulu di Pemeliharaan Data.";
            } else {
                $assoc['lokasi'] = $location->nama_lokasi;
            }
        }

        foreach ($this->textReferenceColumns($kind) as $type => $column) {
            if (blank($assoc[$column] ?? null)) {
                continue;
            }

            $reference = $this->referenceFromText($type, $assoc[$column]);

            if (! $reference) {
                $errors[] = "{$sheetName} baris {$rowNumber}: {$column} tidak cocok dengan master ".CementReferenceValue::labelFor($type).'. Pilih dari dropdown template atau tambahkan dulu di Pemeliharaan Data.';
            } else {
                $assoc[$column] = $reference->name;
            }
        }
    }

    private function validateImportedCertificatePath(mixed $value, string $sheetName, int $rowNumber, array &$errors): void
    {
        $path = trim((string) $value);

        if ($path === '') {
            return;
        }

        if (! str_starts_with($path, 'uploads/sertifikat/')) {
            $errors[] = "{$sheetName} baris {$rowNumber}: file_sertifikat opsional. Kosongkan kolom ini untuk import biasa, lalu upload file PDF/JPG/PNG dari menu Edit Sertifikat setelah data tersimpan.";

            return;
        }

        if (! Storage::disk('local')->exists($path) && ! Storage::disk('public')->exists($path)) {
            $errors[] = "{$sheetName} baris {$rowNumber}: file_sertifikat berisi path {$path}, tetapi file tidak ditemukan di storage aplikasi.";
        }
    }

    private function masterBrand(mixed $id): ?MerekSemen
    {
        $id = $this->parseMasterId($id);

        if (! $id) {
            return null;
        }

        return MerekSemen::query()
            ->with('kategoriSemen')
            ->find($id);
    }

    private function masterLocation(mixed $id): ?LokasiPabrik
    {
        $id = $this->parseMasterId($id);

        if (! $id) {
            return null;
        }

        return LokasiPabrik::query()
            ->where('is_active', true)
            ->find($id);
    }

    private function masterReference(string $type, mixed $id): ?CementReferenceValue
    {
        $id = $this->parseMasterId($id);

        if (! $id) {
            return null;
        }

        return CementReferenceValue::query()
            ->where('type', $type)
            ->where('is_active', true)
            ->find($id);
    }

    private function brandFromText(string $categoryName, string $brandName): ?MerekSemen
    {
        $categoryName = $this->normalizeLookupText($categoryName);
        $brandName = $this->normalizeLookupText($brandName);

        return MerekSemen::query()
            ->with('kategoriSemen')
            ->whereRaw('LOWER(nama_merek) = ?', [$brandName])
            ->whereHas('kategoriSemen', fn ($query) => $query->whereRaw('LOWER(nama_kategori) = ?', [$categoryName]))
            ->first();
    }

    private function locationFromText(string $name): ?LokasiPabrik
    {
        $name = $this->normalizeLookupText($name);

        return LokasiPabrik::query()
            ->whereRaw('LOWER(nama_lokasi) = ?', [$name])
            ->where('is_active', true)
            ->first();
    }

    private function referenceFromText(string $type, string $name): ?CementReferenceValue
    {
        $name = $this->normalizeLookupText($name);

        return CementReferenceValue::query()
            ->where('type', $type)
            ->whereRaw('LOWER(name) = ?', [$name])
            ->where('is_active', true)
            ->first();
    }

    private function normalizeLookupText(string $value): string
    {
        $value = trim($value);
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;

        return mb_strtolower($value);
    }

    private function parseMasterId(mixed $value): ?int
    {
        if (blank($value)) {
            return null;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        preg_match('/^\s*(\d+)/', (string) $value, $matches);

        return isset($matches[1]) ? (int) $matches[1] : null;
    }

    private function masterIdRequiredColumns(string $kind): array
    {
        return match ($kind) {
            'tkdn' => ['merek_id', 'sni_reference_id', 'komoditi_reference_id', 'persentase_tkdn', 'kemasan_reference_id', 'lokasi_pabrik_id', 'berlaku_sd', 'file_sertifikat'],
            'green_label' => ['merek_id', 'sni_reference_id', 'komoditi_reference_id', 'peringkat_green_label_reference_id', 'lokasi_pabrik_id', 'berlaku_sd', 'file_sertifikat'],
            default => ['merek_id', 'sni_reference_id', 'komoditi_reference_id', 'jenis_sertifikasi_reference_id', 'lspro_reference_id', 'lokasi_pabrik_id', 'berlaku_sd', 'file_sertifikat'],
        };
    }

    private function textReferenceColumns(string $kind): array
    {
        return match ($kind) {
            'tkdn' => [
                CementReferenceValue::TYPE_SNI => 'sni',
                CementReferenceValue::TYPE_KOMODITI => 'komoditi',
                CementReferenceValue::TYPE_KEMASAN => 'kemasan',
            ],
            'green_label' => [
                CementReferenceValue::TYPE_SNI => 'sni',
                CementReferenceValue::TYPE_KOMODITI => 'komoditi',
                CementReferenceValue::TYPE_PERINGKAT_GREEN_LABEL => 'peringkat',
            ],
            default => [
                CementReferenceValue::TYPE_SNI => 'sni',
                CementReferenceValue::TYPE_KOMODITI => 'komoditi',
                CementReferenceValue::TYPE_JENIS_SERTIFIKASI => 'jenis_sertifikasi',
                CementReferenceValue::TYPE_LSPRO => 'lspro',
            ],
        };
    }

    private function referenceIdColumn(string $type): ?string
    {
        return match ($type) {
            CementReferenceValue::TYPE_SNI => 'sni_reference_id',
            CementReferenceValue::TYPE_KOMODITI => 'komoditi_reference_id',
            CementReferenceValue::TYPE_LSPRO => 'lspro_reference_id',
            CementReferenceValue::TYPE_JENIS_SERTIFIKASI => 'jenis_sertifikasi_reference_id',
            CementReferenceValue::TYPE_KEMASAN => 'kemasan_reference_id',
            CementReferenceValue::TYPE_PERINGKAT_GREEN_LABEL => 'peringkat_green_label_reference_id',
            default => null,
        };
    }

    private function normalizeDate(mixed $value): ?string
    {
        try {
            if (is_numeric($value)) {
                return ExcelDate::excelToDateTimeObject($value)->format('Y-m-d');
            }

            return Carbon::parse($this->normalizeIndonesianDateText((string) $value))->format('Y-m-d');
        } catch (Throwable) {
            return null;
        }
    }

    private function normalizeIndonesianDateText(string $value): string
    {
        $normalized = trim(strtolower($value));
        $normalized = preg_replace('/\s+/', ' ', $normalized) ?? $normalized;

        $months = [
            'januari' => 'january',
            'jan' => 'january',
            'februari' => 'february',
            'feb' => 'february',
            'maret' => 'march',
            'mar' => 'march',
            'april' => 'april',
            'apr' => 'april',
            'mei' => 'may',
            'may' => 'may',
            'juni' => 'june',
            'jun' => 'june',
            'juli' => 'july',
            'jul' => 'july',
            'agustus' => 'august',
            'agu' => 'august',
            'aug' => 'august',
            'september' => 'september',
            'sep' => 'september',
            'oktober' => 'october',
            'okt' => 'october',
            'oct' => 'october',
            'november' => 'november',
            'nov' => 'november',
            'desember' => 'december',
            'des' => 'december',
            'dec' => 'december',
        ];

        return strtr($normalized, $months);
    }
}
