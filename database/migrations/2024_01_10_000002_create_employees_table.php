<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('employee_code', 20)->unique();
            $table->string('nip', 50)->nullable()->unique();
            $table->string('name', 100);
            $table->enum('gender', ['L', 'P']);
            $table->date('birth_date')->nullable();
            $table->text('address')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email', 100)->nullable()->unique();
            $table->enum('employee_type', ['tetap', 'kontrak', 'honorer', 'outsourcing'])->default('tetap');

            // Doctor specific fields
            $table->boolean('is_doctor')->default(false);
            $table->string('doctor_title', 50)->nullable();
            $table->string('specialization', 100)->nullable();
            $table->string('sip_number', 50)->nullable()->comment('Surat Izin Praktik');
            $table->date('sip_expiry_date')->nullable();
            $table->string('str_number', 50)->nullable()->comment('Surat Tanda Registrasi');
            $table->date('str_expiry_date')->nullable();
            $table->foreignId('specialist_polyclinic_id')->nullable()->constrained('polyclinics');

            // Nurse specific fields
            $table->boolean('is_nurse')->default(false);
            $table->string('sip_nurse_number', 50)->nullable();

            // Other profession
            $table->string('profession', 50)->nullable();
            $table->string('certification_number', 50)->nullable();

            $table->date('join_date');
            $table->date('resign_date')->nullable();
            $table->enum('status', ['aktif', 'cuti', 'nonaktif', 'pensiun'])->default('aktif');
            $table->string('photo_path', 255)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['employee_code']);
            $table->index(['nip']);
            $table->index(['name']);
            $table->index(['is_doctor', 'status']);
            $table->index(['is_nurse', 'status']);
            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
