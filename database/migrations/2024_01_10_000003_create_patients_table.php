<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->string('medical_record_number', 20)->unique()->comment('Nomor Rekam Medis');
            $table->string('nik', 16)->nullable()->unique()->comment('Nomor Induk Kependudukan');
            $table->string('bpjs_number', 20)->nullable()->unique()->comment('Nomor Kartu BPJS');
            $table->string('bpjs_ppk_code', 10)->nullable();
            $table->enum('bpjs_class', ['Kelas I', 'Kelas II', 'Kelas III', 'Non-BPJS'])->default('Non-BPJS');

            // Personal Information
            $table->string('name', 100);
            $table->enum('gender', ['male', 'female']);
            $table->string('birth_place', 50)->nullable();
            $table->date('birth_date')->nullable();
            $table->enum('blood_type', ['A', 'B', 'AB', 'O', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-', 'Tidak Tahu'])->nullable();
            $table->enum('marital_status', ['Belum Menikah', 'Menikah', 'Cerai', 'Duda/Janda'])->nullable();
            $table->string('education', 50)->nullable();
            $table->string('occupation', 50)->nullable();
            $table->string('nationality', 50)->default('Indonesia');
            $table->string('religion', 30)->nullable();

            // Address
            $table->text('address');
            $table->string('rt', 3)->nullable();
            $table->string('rw', 3)->nullable();
            $table->string('village', 50)->nullable()->comment('Kelurahan/Desa');
            $table->string('district', 50)->nullable()->comment('Kecamatan');
            $table->string('city', 50)->nullable()->comment('Kabupaten/Kota');
            $table->string('province', 50)->nullable();
            $table->string('postal_code', 10)->nullable();

            // Contact
            $table->string('phone_primary', 20)->nullable();
            $table->string('phone_secondary', 20)->nullable();
            $table->string('email', 100)->nullable();

            // Emergency Contact
            $table->string('emergency_name', 100)->nullable();
            $table->string('emergency_relation', 50)->nullable();
            $table->string('emergency_phone', 20)->nullable();
            $table->text('emergency_address')->nullable();

            // Insurance Information
            $table->string('insurance_name', 100)->nullable();
            $table->string('insurance_number', 50)->nullable();
            $table->string('insurance_card_path', 255)->nullable();

            // Mother Information (for newborn)
            $table->foreignId('mother_patient_id')->nullable()->constrained('patients')->comment('For newborn patients');

            $table->timestamp('first_visit_at')->nullable();
            $table->timestamp('last_visit_at')->nullable();
            $table->integer('total_visits')->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['medical_record_number']);
            $table->index(['nik']);
            $table->index(['bpjs_number']);
            $table->index(['name']);
            if (config('database.default') !== 'sqlite') {
                $table->fullText(['name']);
            }
            $table->index(['gender', 'birth_date']);
            $table->index(['is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
