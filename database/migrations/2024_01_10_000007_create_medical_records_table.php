<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medical_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->constrained();
            $table->foreignId('patient_id')->constrained();
            $table->string('record_number', 30)->unique();
            $table->enum('record_type', ['rawat_jalan', 'rawat_inap', 'igd', 'mcu']);
            $table->enum('status', ['draft', 'completed', 'locked'])->default('draft');
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('completed_by')->nullable()->constrained('users');
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['patient_id', 'created_at']);
            $table->index(['visit_id']);
            $table->index(['record_number']);
            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_records');
    }
};
