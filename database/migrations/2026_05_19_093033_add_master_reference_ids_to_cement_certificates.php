<?php

use App\Models\CementReferenceValue;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const FOREIGN_KEYS = [
        'sertifikat_sni' => [
            'sni_reference_id' => ['cement_reference_values', 'sni_ref_fk'],
            'komoditi_reference_id' => ['cement_reference_values', 'sni_komoditi_ref_fk'],
            'jenis_sertifikasi_reference_id' => ['cement_reference_values', 'sni_jenis_ref_fk'],
            'lspro_reference_id' => ['cement_reference_values', 'sni_lspro_ref_fk'],
            'lokasi_pabrik_id' => ['lokasi_pabrik', 'sni_lokasi_fk'],
        ],
        'sertifikat_tkdn' => [
            'sni_reference_id' => ['cement_reference_values', 'tkdn_sni_ref_fk'],
            'komoditi_reference_id' => ['cement_reference_values', 'tkdn_komoditi_ref_fk'],
            'kemasan_reference_id' => ['cement_reference_values', 'tkdn_kemasan_ref_fk'],
            'lokasi_pabrik_id' => ['lokasi_pabrik', 'tkdn_lokasi_fk'],
        ],
        'sertifikat_green_label' => [
            'sni_reference_id' => ['cement_reference_values', 'gl_sni_ref_fk'],
            'komoditi_reference_id' => ['cement_reference_values', 'gl_komoditi_ref_fk'],
            'peringkat_green_label_reference_id' => ['cement_reference_values', 'gl_peringkat_ref_fk'],
            'lokasi_pabrik_id' => ['lokasi_pabrik', 'gl_lokasi_fk'],
        ],
    ];

    public function up(): void
    {
        foreach (self::FOREIGN_KEYS as $tableName => $columns) {
            foreach (array_keys($columns) as $column) {
                $this->addColumnIfMissing($tableName, $column);
            }
        }

        $this->addForeignKeysIfMissing();

        $this->backfillReferenceIds('sertifikat_sni', [
            'sni_reference_id' => [CementReferenceValue::TYPE_SNI, 'sni'],
            'komoditi_reference_id' => [CementReferenceValue::TYPE_KOMODITI, 'komoditi'],
            'jenis_sertifikasi_reference_id' => [CementReferenceValue::TYPE_JENIS_SERTIFIKASI, 'jenis_sertifikasi'],
            'lspro_reference_id' => [CementReferenceValue::TYPE_LSPRO, 'lspro'],
        ]);
        $this->backfillLocationIds('sertifikat_sni');

        $this->backfillReferenceIds('sertifikat_tkdn', [
            'sni_reference_id' => [CementReferenceValue::TYPE_SNI, 'sni'],
            'komoditi_reference_id' => [CementReferenceValue::TYPE_KOMODITI, 'komoditi'],
            'kemasan_reference_id' => [CementReferenceValue::TYPE_KEMASAN, 'kemasan'],
        ]);
        $this->backfillLocationIds('sertifikat_tkdn');

        $this->backfillReferenceIds('sertifikat_green_label', [
            'sni_reference_id' => [CementReferenceValue::TYPE_SNI, 'sni'],
            'komoditi_reference_id' => [CementReferenceValue::TYPE_KOMODITI, 'komoditi'],
            'peringkat_green_label_reference_id' => [CementReferenceValue::TYPE_PERINGKAT_GREEN_LABEL, 'peringkat'],
        ]);
        $this->backfillLocationIds('sertifikat_green_label');
    }

    public function down(): void
    {
        foreach (array_reverse(self::FOREIGN_KEYS) as $tableName => $columns) {
            foreach (array_reverse($columns) as $column => [, $constraintName]) {
                $this->dropForeignIfExists($tableName, $column, $constraintName);
            }

            foreach (array_reverse(array_keys($columns)) as $column) {
                if (Schema::hasColumn($tableName, $column)) {
                    Schema::table($tableName, fn (Blueprint $table) => $table->dropColumn($column));
                }
            }
        }
    }

    private function addColumnIfMissing(string $tableName, string $column): void
    {
        if (Schema::hasColumn($tableName, $column)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($column): void {
            $table->unsignedBigInteger($column)->nullable();
        });
    }

    private function addForeignKeysIfMissing(): void
    {
        foreach (self::FOREIGN_KEYS as $tableName => $columns) {
            foreach ($columns as $column => [$referencedTable, $constraintName]) {
                if ($this->foreignKeyExists($tableName, $column)) {
                    continue;
                }

                Schema::table($tableName, function (Blueprint $table) use ($column, $referencedTable, $constraintName): void {
                    $table->foreign($column, $constraintName)
                        ->references('id')
                        ->on($referencedTable)
                        ->nullOnDelete();
                });
            }
        }
    }

    /**
     * @param  array<string, array{0: string, 1: string}>  $columns
     */
    private function backfillReferenceIds(string $table, array $columns): void
    {
        foreach ($columns as $idColumn => [$type, $nameColumn]) {
            DB::table($table)
                ->select(['id', $nameColumn])
                ->whereNull($idColumn)
                ->whereNotNull($nameColumn)
                ->orderBy('id')
                ->each(function (object $certificate) use ($table, $idColumn, $type, $nameColumn): void {
                    $referenceId = DB::table('cement_reference_values')
                        ->where('type', $type)
                        ->where('name', $certificate->{$nameColumn})
                        ->value('id');

                    if ($referenceId) {
                        DB::table($table)
                            ->where('id', $certificate->id)
                            ->update([$idColumn => $referenceId]);
                    }
                });
        }
    }

    private function backfillLocationIds(string $table): void
    {
        DB::table($table)
            ->select(['id', 'lokasi'])
            ->whereNull('lokasi_pabrik_id')
            ->whereNotNull('lokasi')
            ->orderBy('id')
            ->each(function (object $certificate) use ($table): void {
                $locationId = DB::table('lokasi_pabrik')
                    ->where('nama_lokasi', $certificate->lokasi)
                    ->value('id');

                if ($locationId) {
                    DB::table($table)
                        ->where('id', $certificate->id)
                        ->update(['lokasi_pabrik_id' => $locationId]);
                }
            });
    }

    private function foreignKeyExists(string $tableName, string $column): bool
    {
        if (DB::getDriverName() === 'sqlite') {
            return collect(DB::select("PRAGMA foreign_key_list('{$tableName}')"))
                ->contains(fn (object $foreignKey) => $foreignKey->from === $column);
        }

        return DB::table('information_schema.key_column_usage')
            ->whereRaw('table_schema = database()')
            ->where('table_name', $tableName)
            ->where('column_name', $column)
            ->whereNotNull('referenced_table_name')
            ->exists();
    }

    private function dropForeignIfExists(string $tableName, string $column, string $constraintName): void
    {
        if (! $this->foreignKeyExists($tableName, $column)) {
            return;
        }

        $name = DB::getDriverName() === 'mysql'
            ? DB::table('information_schema.key_column_usage')
                ->whereRaw('table_schema = database()')
                ->where('table_name', $tableName)
                ->where('column_name', $column)
                ->whereNotNull('referenced_table_name')
                ->value('constraint_name')
            : $constraintName;

        Schema::table($tableName, fn (Blueprint $table) => $table->dropForeign($name ?: $constraintName));
    }
};
