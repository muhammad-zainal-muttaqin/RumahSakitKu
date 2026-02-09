<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cppts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medical_record_id')->constrained();
            $table->foreignId('patient_id')->constrained();
            $table->foreignId('visit_id')->constrained();
            $table->date('cppt_date')->nullable();
            $table->time('cppt_time')->nullable();

            // SOAP Format
            $table->text('subjective')->nullable()->comment('Keluhan dan riwayat pasien');
            $table->text('objective')->nullable()->comment('Hasil pemeriksaan fisik dan penunjang');
            $table->text('assessment')->nullable()->comment('Diagnosis dan analisis');
            $table->text('plan')->nullable()->comment('Rencana tatalaksana');
            $table->text('instruction')->nullable()->comment('Instruksi');
            $table->text('progress_notes')->nullable()->comment('Catatan perkembangan');

            // Verification
            $table->foreignId('verified_by')->nullable()->constrained('employees')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->boolean('is_verified')->default(false);

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['medical_record_id']);
            $table->index(['visit_id']);
            $table->index(['patient_id', 'documented_at']);
            $table->index(['documented_by']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cppts');
    }
};
