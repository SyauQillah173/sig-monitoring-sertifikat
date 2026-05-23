<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('iso_standards', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('sertifikat_sistem_semen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lokasi_pabrik_id')->constrained('lokasi_pabrik')->restrictOnDelete();
            $table->foreignId('iso_standard_id')->constrained('iso_standards')->restrictOnDelete();
            $table->string('certificate_number')->unique();
            $table->string('issuer')->nullable();
            $table->string('audit_stage', 40)->index();
            $table->string('scope')->nullable();
            $table->date('issued_at');
            $table->date('berlaku_sd')->index();
            $table->string('file_sertifikat', 2048)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['lokasi_pabrik_id', 'iso_standard_id'], 'sys_cert_loc_iso_idx');
            $table->index(['iso_standard_id', 'berlaku_sd'], 'sys_cert_iso_exp_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sertifikat_sistem_semen');
        Schema::dropIfExists('iso_standards');
    }
};
