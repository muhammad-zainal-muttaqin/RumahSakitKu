<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visit_queues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->constrained();
            $table->foreignId('patient_id')->constrained();
            $table->foreignId('polyclinic_id')->constrained();
            $table->integer('queue_number');
            $table->string('display_number', 10)->comment('Nomor antrian untuk ditampilkan, contoh: A001');
            $table->enum('status', ['waiting', 'called', 'in_progress', 'completed', 'cancelled', 'skipped'])->default('waiting');
            $table->timestamp('called_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('counter_number', 10)->nullable()->comment('Nomor loket');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['visit_id']);
            $table->index(['patient_id']);
            $table->index(['polyclinic_id']);
            $table->index(['queue_number']);
            $table->index(['display_number']);
            $table->index(['status']);
            $table->index(['called_at']);
            $table->index(['completed_at']);

            // Composite indexes
            $table->index(['polyclinic_id', 'status', 'queue_number']);
            $table->index(['polyclinic_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visit_queues');
    }
};
