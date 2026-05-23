<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('sertifikat_sistem_semen', 'acquisition_year')) {
            Schema::table('sertifikat_sistem_semen', function (Blueprint $table) {
                $table->unsignedSmallInteger('acquisition_year')->nullable()->after('berlaku_sd')->index();
                $table->string('certification_level', 30)->nullable()->after('acquisition_year')->index();
                $table->string('certification_category')->nullable()->after('certification_level');
                $table->string('process_owner')->nullable()->after('certification_category');
                $table->string('accreditation_number')->nullable()->after('process_owner');
                $table->string('public_url', 2048)->nullable()->after('accreditation_number');
                $table->text('description')->nullable()->after('public_url');
            });
        }

        if (! Schema::hasTable('sertifikat_sistem_audit_events')) {
            Schema::create('sertifikat_sistem_audit_events', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('sertifikat_sistem_semen_id');
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('audit_type', 40)->index();
                $table->date('target_date')->nullable()->index();
                $table->date('completed_at')->nullable();
                $table->string('status', 30)->default('pending')->index();
                $table->string('evidence_file', 2048)->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->foreign('sertifikat_sistem_semen_id', 'sys_audit_cert_fk')->references('id')->on('sertifikat_sistem_semen')->cascadeOnDelete();
                $table->foreign('user_id', 'sys_audit_user_fk')->references('id')->on('users')->nullOnDelete();
                $table->unique(['sertifikat_sistem_semen_id', 'audit_type'], 'sys_audit_cert_type_unique');
                $table->index(['status', 'target_date'], 'sys_audit_status_target_idx');
            });
        }

        $this->backfillAuditEvents();
    }

    public function down(): void
    {
        Schema::dropIfExists('sertifikat_sistem_audit_events');

        Schema::table('sertifikat_sistem_semen', function (Blueprint $table) {
            $table->dropColumn([
                'acquisition_year',
                'certification_level',
                'certification_category',
                'process_owner',
                'accreditation_number',
                'public_url',
                'description',
            ]);
        });
    }

    private function backfillAuditEvents(): void
    {
        DB::table('sertifikat_sistem_semen')
            ->orderBy('id')
            ->get()
            ->each(function ($certificate): void {
                $issuedAt = Carbon::parse($certificate->issued_at)->startOfDay();
                $expiresAt = Carbon::parse($certificate->berlaku_sd)->startOfDay();
                $now = now();

                $events = [
                    [
                        'audit_type' => 'initial',
                        'target_date' => $issuedAt->toDateString(),
                        'completed_at' => $issuedAt->toDateString(),
                        'status' => 'completed',
                    ],
                    [
                        'audit_type' => 'surveilen_1',
                        'target_date' => $this->targetDate($issuedAt, $expiresAt, 1),
                        'completed_at' => in_array($certificate->audit_stage, ['surveilen_2', 'renewal'], true) ? $this->targetDate($issuedAt, $expiresAt, 1) : null,
                        'status' => in_array($certificate->audit_stage, ['surveilen_2', 'renewal'], true) ? 'completed' : 'pending',
                    ],
                    [
                        'audit_type' => 'surveilen_2',
                        'target_date' => $this->targetDate($issuedAt, $expiresAt, 2),
                        'completed_at' => $certificate->audit_stage === 'renewal' ? $this->targetDate($issuedAt, $expiresAt, 2) : null,
                        'status' => $certificate->audit_stage === 'renewal' ? 'completed' : ($certificate->audit_stage === 'surveilen_2' ? 'pending' : 'upcoming'),
                    ],
                    [
                        'audit_type' => 'renewal',
                        'target_date' => $expiresAt->toDateString(),
                        'completed_at' => null,
                        'status' => $certificate->audit_stage === 'renewal' ? 'pending' : 'upcoming',
                    ],
                ];

                foreach ($events as $event) {
                    DB::table('sertifikat_sistem_audit_events')->updateOrInsert(
                        [
                            'sertifikat_sistem_semen_id' => $certificate->id,
                            'audit_type' => $event['audit_type'],
                        ],
                        [
                            'target_date' => $event['target_date'],
                            'completed_at' => $event['completed_at'],
                            'status' => $event['status'],
                            'created_at' => $now,
                            'updated_at' => $now,
                        ],
                    );
                }
            });
    }

    private function targetDate(Carbon $issuedAt, Carbon $expiresAt, int $years): string
    {
        $targetDate = $issuedAt->copy()->addYears($years);

        return ($targetDate->gt($expiresAt) ? $expiresAt : $targetDate)->toDateString();
    }
};
