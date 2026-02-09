<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('surgeries', function (Blueprint $table) {
            $table->id();
            $table->string('surgery_number', 30)->unique();
            $table->foreignId('visit_id')->constrained();
            $table->foreignId('patient_id')->constrained();

            // Scheduling
            $table->date('scheduled_date')->nullable();
            $table->timestamp('start_time')->nullable();
            $table->timestamp('estimated_end_time')->nullable();
            $table->timestamp('actual_start')->nullable();
            $table->timestamp('actual_end')->nullable();

            $table->string('operating_room', 20)->nullable();

            // Team
            $table->foreignId('surgeon_id')->nullable()->constrained('employees');
            $table->foreignId('assistant_surgeon_id')->nullable()->constrained('employees');
            $table->foreignId('anesthesiologist_id')->nullable()->constrained('employees');
            $table->string('anesthesia_type', 30)->nullable();
            $table->foreignId('nurse_id')->nullable()->constrained('employees');
            $table->foreignId('circulating_nurse_id')->nullable()->constrained('employees');

            // Diagnosis & Procedure
            $table->text('pre_diagnosis')->nullable();
            $table->text('post_diagnosis')->nullable();
            $table->string('procedure_name', 191)->nullable();
            $table->string('procedure_code', 20)->nullable();

            // Pricing
            $table->decimal('total_price', 12, 2)->nullable();

            // Type & Status
            $table->enum('surgery_type', ['elektif', 'urgent', 'cito', 'emergency'])->default('elektif');
            $table->enum('status', ['scheduled', 'preparation', 'in_progress', 'completed', 'cancelled'])->default('scheduled');

            // Safety Checklist
            $table->boolean('safety_checklist_sign_in')->default(false);
            $table->timestamp('safety_checklist_sign_in_at')->nullable();
            $table->boolean('safety_checklist_time_out')->default(false);
            $table->timestamp('safety_checklist_time_out_at')->nullable();
            $table->boolean('safety_checklist_sign_out')->default(false);
            $table->timestamp('safety_checklist_sign_out_at')->nullable();

            // Operative Details
            $table->text('procedure_notes')->nullable();
            $table->text('findings')->nullable();
            $table->text('complications')->nullable();
            $table->text('specimens')->nullable();

            // Postponement
            $table->boolean('is_postponed')->default(false);
            $table->text('postponed_reason')->nullable();
            $table->timestamp('postponed_at')->nullable();

            // Cancellation
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users');
            $table->text('cancellation_reason')->nullable();

            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['surgery_number']);
            $table->index(['visit_id']);
            $table->index(['patient_id']);
            $table->index(['scheduled_date']);
            $table->index(['status']);
            $table->index(['surgeon_id']);
            $table->index(['operating_room']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('surgeries');
    }
};
