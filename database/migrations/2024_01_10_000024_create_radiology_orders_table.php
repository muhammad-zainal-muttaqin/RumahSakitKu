<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('radiology_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 30)->unique();
            $table->foreignId('visit_id')->constrained();
            $table->foreignId('patient_id')->constrained();
            $table->foreignId('doctor_id')->nullable()->constrained('employees');
            $table->foreignId('medical_record_id')->nullable()->constrained();

            $table->string('examination_type', 50);
            $table->string('body_area', 100)->nullable();
            $table->string('position', 50)->nullable();

            $table->boolean('contrast')->default(false);
            $table->string('contrast_type', 50)->nullable();
            $table->text('clinical_indication')->nullable();

            $table->timestamp('scheduled_date')->nullable();
            $table->enum('priority', ['normal', 'urgent', 'emergency'])->default('normal');
            $table->enum('status', ['pending', 'scheduled', 'in_progress', 'completed', 'reported', 'cancelled'])->default('pending');

            $table->decimal('total_price', 12, 2)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['order_number']);
            $table->index(['visit_id']);
            $table->index(['patient_id']);
            $table->index(['status']);
            $table->index(['scheduled_date']);
            $table->index(['examination_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('radiology_orders');
    }
};
