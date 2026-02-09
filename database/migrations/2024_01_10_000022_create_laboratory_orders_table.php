<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('laboratory_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 30)->unique();
            $table->foreignId('visit_id')->constrained();
            $table->foreignId('patient_id')->constrained();
            $table->foreignId('doctor_id')->nullable()->constrained('employees');
            $table->foreignId('medical_record_id')->nullable()->constrained();

            $table->timestamp('order_date');
            $table->enum('priority', ['normal', 'urgent', 'cito'])->default('normal');
            $table->enum('status', ['pending', 'in_progress', 'completed', 'validated', 'cancelled'])->default('pending');

            $table->text('diagnosis_notes')->nullable();
            $table->text('clinical_notes')->nullable();
            $table->decimal('total_price', 12, 2)->nullable();
            $table->boolean('is_cito')->default(false);

            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['order_number']);
            $table->index(['visit_id']);
            $table->index(['patient_id']);
            $table->index(['status']);
            $table->index(['order_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laboratory_orders');
    }
};
