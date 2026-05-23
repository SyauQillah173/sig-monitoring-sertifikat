<?php

use App\Enums\CertificateStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('certificate_type_id')->constrained()->restrictOnDelete();
            $table->foreignId('issuer_id')->constrained()->restrictOnDelete();
            $table->foreignId('issued_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('certificate_number')->unique();
            $table->string('title')->nullable();
            $table->date('issued_at');
            $table->date('expires_at')->index();
            $table->string('file_path', 2048)->nullable();
            $table->string('status')->default(CertificateStatus::Active->value)->index();
            $table->text('notes')->nullable();
            $table->timestamp('last_notified_at')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'certificate_type_id']);
            $table->index(['issuer_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
