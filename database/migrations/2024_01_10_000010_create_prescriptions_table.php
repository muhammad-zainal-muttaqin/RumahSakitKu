<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id();
            $table->string('prescription_number', 30)->unique();
            $table->foreignId('patient_id')->constrained();
            $table->foreignId('visit_id')->constrained();
            $table->foreignId('medical_record_id')->constrained();
            $table->date('prescription_date')->nullable();
            $table->enum('prescription_type', ['regular', 'emergency', 'compound', 'non_racikan', 'racikan', 'cito'])->default('regular');
            $table->enum('priority', ['normal', 'urgent'])->default('normal');
            $table->enum('status', ['pending', 'verified', 'processing', 'completed', 'rejected', 'dispensed', 'cancelled'])->default('pending');
            $table->text('clinical_indication')->nullable();
            $table->text('allergies')->nullable();
            $table->foreignId('prescribed_by')->nullable()->constrained('employees')->nullOnDelete();
            $table->boolean('verified_by_pharmacist')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('dispensed_at')->nullable();
            $table->foreignId('dispensed_by')->nullable()->constrained('employees')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['prescription_number']);
            $table->index(['visit_id']);
            $table->index(['patient_id', 'prescribed_at']);
            $table->index(['status']);
            $table->index(['prescribed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prescriptions');
    }
};
