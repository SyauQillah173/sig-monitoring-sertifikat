<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('perusahaan_semen', function (Blueprint $table) {
            $table->id();
            $table->string('nama_perusahaan')->unique();
            $table->string('kode')->nullable()->unique();
            $table->text('alamat')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('kontak_perusahaan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('perusahaan_semen_id')->constrained('perusahaan_semen')->cascadeOnDelete();
            $table->string('nama_pic');
            $table->string('jabatan')->nullable();
            $table->string('email')->index();
            $table->string('phone')->nullable();
            $table->boolean('is_primary')->default(false)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->unique(['perusahaan_semen_id', 'email']);
        });

        Schema::create('certificate_email_notification_logs', function (Blueprint $table) {
            $table->id();
            $table->string('certificate_type')->nullable();
            $table->unsignedBigInteger('certificate_id')->nullable();
            $table->foreignId('kontak_perusahaan_id')->nullable()->constrained('kontak_perusahaan')->nullOnDelete();
            $table->string('recipient_email')->index();
            $table->string('notification_type')->index();
            $table->date('certificate_expires_at')->nullable()->index();
            $table->string('status')->default('sent')->index();
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable()->index();
            $table->timestamps();

            $table->index(['certificate_type', 'certificate_id'], 'cert_email_logs_cert_idx');
            $table->index(['notification_type', 'sent_at'], 'cert_email_logs_type_sent_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificate_email_notification_logs');
        Schema::dropIfExists('kontak_perusahaan');
        Schema::dropIfExists('perusahaan_semen');
        Schema::dropIfExists('notification_settings');
    }
};
