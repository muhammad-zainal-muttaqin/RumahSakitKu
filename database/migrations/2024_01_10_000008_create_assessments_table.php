<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medical_record_id')->constrained();
            $table->foreignId('visit_id')->constrained();
            $table->foreignId('patient_id')->constrained();
            $table->foreignId('assessed_by')->constrained('employees');

            // Vital Signs (TTV)
            $table->decimal('systolic_bp', 5, 2)->nullable()->comment('Tekanan darah sistolik mmHg');
            $table->decimal('diastolic_bp', 5, 2)->nullable()->comment('Tekanan darah diastolik mmHg');
            $table->decimal('pulse_rate', 5, 2)->nullable()->comment('Denyut nadi /menit');
            $table->decimal('respiratory_rate', 5, 2)->nullable()->comment('Frekuensi napas /menit');
            $table->decimal('body_temperature', 4, 2)->nullable()->comment('Suhu tubuh Celsius');
            $table->decimal('oxygen_saturation', 5, 2)->nullable()->comment('SpO2 %');
            $table->decimal('blood_glucose', 5, 2)->nullable()->comment('Gula darah mg/dL');
            $table->decimal('weight', 6, 2)->nullable()->comment('Berat badan kg');
            $table->decimal('height', 6, 2)->nullable()->comment('Tinggi badan cm');
            $table->decimal('bmi', 5, 2)->nullable()->comment('Body Mass Index');
            $table->decimal('pain_scale', 3, 1)->nullable()->comment('Skala nyeri 0-10');

            // Pain Assessment
            $table->enum('pain_location', ['kepala', 'dada', 'perut', 'punggung', 'tangan', 'kaki', 'lainnya'])->nullable();
            $table->text('pain_description')->nullable();

            // Consciousness
            $table->enum('consciousness', ['compos_mentis', 'somnolence', 'sopor', 'coma'])->default('compos_mentis');
            $table->integer('gcs_eye')->nullable()->comment('Glasgow Coma Scale - Eye');
            $table->integer('gcs_verbal')->nullable()->comment('Glasgow Coma Scale - Verbal');
            $table->integer('gcs_motor')->nullable()->comment('Glasgow Coma Scale - Motor');
            $table->integer('gcs_total')->nullable();

            // Fall Risk Assessment
            $table->enum('fall_risk', ['rendah', 'sedang', 'tinggi'])->nullable();
            $table->json('fall_risk_factors')->nullable();

            // Allergy Information
            $table->text('allergy_history')->nullable();
            $table->text('drug_allergy')->nullable();
            $table->text('food_allergy')->nullable();

            // Assessment
            $table->text('chief_complaint')->comment('Keluhan utama');
            $table->text('present_illness_history')->nullable()->comment('Riwayat penyakit sekarang');
            $table->text('past_medical_history')->nullable()->comment('Riwayat penyakit dahulu');
            $table->text('family_history')->nullable()->comment('Riwayat penyakit keluarga');
            $table->text('social_history')->nullable()->comment('Riwayat sosial');

            // Physical Examination
            $table->text('general_condition')->nullable();
            $table->text('head_examination')->nullable();
            $table->text('neck_examination')->nullable();
            $table->text('thorax_examination')->nullable();
            $table->text('heart_examination')->nullable();
            $table->text('lung_examination')->nullable();
            $table->text('abdomen_examination')->nullable();
            $table->text('extremities_examination')->nullable();
            $table->text('neurological_examination')->nullable();
            $table->text('skin_examination')->nullable();

            // Diagnosis
            $table->string('primary_diagnosis_code', 10)->nullable()->comment('ICD-10');
            $table->text('primary_diagnosis_name')->nullable();
            $table->json('secondary_diagnoses')->nullable()->comment('Array of ICD-10 codes');
            $table->enum('diagnosis_type', ['primer', 'sekunder', 'komplikasi'])->default('primer');

            $table->timestamp('assessed_at');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['medical_record_id']);
            $table->index(['visit_id']);
            $table->index(['patient_id']);
            $table->index(['assessed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessments');
    }
};
