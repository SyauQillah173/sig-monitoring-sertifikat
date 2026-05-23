<?php

use App\Enums\CertificateRenewalStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificate_renewals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('certificate_id')->constrained()->cascadeOnDelete();
            $table->foreignId('renewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('renewal_number')->default(1);
            $table->string('previous_certificate_number')->nullable();
            $table->string('new_certificate_number')->nullable();
            $table->date('renewal_date');
            $table->date('previous_expires_at')->nullable();
            $table->date('new_expires_at')->nullable();
            $table->string('file_path', 2048)->nullable();
            $table->string('status')->default(CertificateRenewalStatus::Pending->value)->index();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['certificate_id', 'renewal_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificate_renewals');
    }
};
