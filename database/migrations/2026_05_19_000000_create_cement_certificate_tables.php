<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kategori_semen', function (Blueprint $table) {
            $table->id();
            $table->string('nama_kategori')->unique();
            $table->timestamps();
        });

        Schema::create('merek_semen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kategori_semen_id')->constrained('kategori_semen')->restrictOnDelete();
            $table->string('nama_merek');
            $table->timestamps();

            $table->unique(['kategori_semen_id', 'nama_merek']);
            $table->index('kategori_semen_id');
        });

        Schema::create('sertifikat_sni', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merek_semen_id')->constrained('merek_semen')->restrictOnDelete();
            $table->string('sni')->index();
            $table->string('komoditi')->index();
            $table->string('jenis_sertifikasi');
            $table->string('lspro')->index();
            $table->string('lokasi')->index();
            $table->date('berlaku_sd')->index();
            $table->string('file_sertifikat', 2048)->nullable();
            $table->timestamps();

            $table->index(['merek_semen_id', 'berlaku_sd']);
        });

        Schema::create('sertifikat_tkdn', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merek_semen_id')->constrained('merek_semen')->restrictOnDelete();
            $table->string('sni')->index();
            $table->string('komoditi')->index();
            $table->decimal('persentase_tkdn', 5, 2);
            $table->string('kemasan');
            $table->string('lokasi')->index();
            $table->date('berlaku_sd')->index();
            $table->string('file_sertifikat', 2048)->nullable();
            $table->timestamps();

            $table->index(['merek_semen_id', 'berlaku_sd']);
        });

        Schema::create('sertifikat_green_label', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merek_semen_id')->constrained('merek_semen')->restrictOnDelete();
            $table->string('sni')->index();
            $table->string('komoditi')->index();
            $table->string('peringkat');
            $table->string('lokasi')->index();
            $table->date('berlaku_sd')->index();
            $table->string('file_sertifikat', 2048)->nullable();
            $table->timestamps();

            $table->index(['merek_semen_id', 'berlaku_sd']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sertifikat_green_label');
        Schema::dropIfExists('sertifikat_tkdn');
        Schema::dropIfExists('sertifikat_sni');
        Schema::dropIfExists('merek_semen');
        Schema::dropIfExists('kategori_semen');
    }
};
