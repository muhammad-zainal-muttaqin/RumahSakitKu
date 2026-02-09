<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visits', function (Blueprint $table) {
            $table->id();
            $table->string('visit_number', 30)->unique();
            $table->foreignId('patient_id')->constrained();

            // Visit Classification
            $table->enum('visit_type', ['rawat_jalan', 'rawat_inap', 'igd', 'mcu']);
            $table->enum('visit_status', ['pendaftaran', 'menunggu', 'proses', 'selesai', 'batal'])->default('pendaftaran');
            $table->enum('payment_type', ['bpjs', 'umum', 'asuransi', 'perusahaan', 'gratis'])->default('umum');
            $table->enum('priority', ['normal', 'darurat', 'prioritas', 'emergency'])->default('normal');

            // Registration
            $table->timestamp('registration_date');
            $table->foreignId('polyclinic_id')->nullable()->constrained();
            $table->foreignId('doctor_id')->nullable()->constrained('employees');
            $table->foreignId('registered_by')->constrained('users');

            // BPJS Information
            $table->string('bpjs_sep_number', 30)->nullable()->comment('Nomor SEP BPJS');
            $table->string('bpjs_rujukan_number', 30)->nullable();
            $table->date('bpjs_rujukan_date')->nullable();
            $table->foreignId('bpjs_ppk_rujukan')->nullable()->constrained('polyclinics');
            $table->enum('bpjs_care_type', ['1', '2'])->nullable()->comment('1: Rujukan FKTP, 2: Rujukan antar RS');
            $table->string('bpjs_diagnosis_code', 10)->nullable();
            $table->string('bpjs_procedure_code', 10)->nullable();
            $table->enum('bpjs_katarak', ['0', '1'])->default('0');
            $table->enum('bpjs_cob', ['0', '1'])->default('0');
            $table->enum('bpjs_kasus_laka', ['0', '1'])->default('0');
            $table->string('bpjs_laka_no_suplesi', 30)->nullable();
            $table->date('bpjs_laka_tgl_kejadian')->nullable();
            $table->text('bpjs_laka_keterangan')->nullable();
            $table->enum('bpjs_suplesi', ['0', '1'])->default('0');

            // Inpatient Information
            $table->foreignId('room_id')->nullable()->constrained();
            $table->foreignId('bed_id')->nullable()->constrained();
            $table->timestamp('admission_date')->nullable();
            $table->timestamp('discharge_date')->nullable();
            $table->enum('discharge_status', ['pulang', 'dirujuk', 'meninggal', 'lari', 'pindah_rs'])->nullable();

            // Referral Information
            $table->foreignId('referred_from_polyclinic_id')->nullable()->constrained('polyclinics');
            $table->foreignId('referred_to_polyclinic_id')->nullable()->constrained('polyclinics');
            $table->text('referral_reason')->nullable();

            // Insurance/Company
            $table->string('insurance_name', 100)->nullable();
            $table->string('insurance_number', 50)->nullable();
            $table->string('company_name', 100)->nullable();
            $table->string('company_pic', 100)->nullable();

            // Queue System
            $table->string('queue_number', 20)->nullable();
            $table->integer('queue_display_number')->nullable();
            $table->timestamp('called_at')->nullable();
            $table->foreignId('called_by')->nullable()->constrained('users');

            // Visit Flow Timestamps
            $table->timestamp('arrived_at')->nullable();
            $table->timestamp('triage_at')->nullable();
            $table->timestamp('assessment_at')->nullable();
            $table->timestamp('examination_at')->nullable();
            $table->timestamp('prescription_at')->nullable();
            $table->timestamp('payment_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            // Metadata
            $table->text('notes')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['visit_number']);
            $table->index(['patient_id', 'registration_date']);
            $table->index(['visit_type', 'visit_status']);
            $table->index(['polyclinic_id', 'registration_date', 'visit_status']);
            $table->index(['doctor_id', 'registration_date']);
            $table->index(['bpjs_sep_number']);
            $table->index(['queue_number', 'polyclinic_id', 'registration_date']);
            $table->index(['visit_status', 'priority']);
            $table->index(['registration_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visits');
    }
};
