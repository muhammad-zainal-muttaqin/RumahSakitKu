<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('radiology_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('radiology_order_id')->constrained();
            $table->json('result_images')->nullable();
            $table->text('report_text')->nullable();

            $table->text('conclusion')->nullable();
            $table->text('recommendation')->nullable();
            $table->foreignId('radiologist_id')->nullable()->constrained('employees');
            $table->timestamp('reported_at')->nullable();

            $table->text('technician_notes')->nullable();
            $table->json('exposure_parameters')->nullable();
            $table->string('dose_info', 191)->nullable();
            $table->string('quality_assurance', 191)->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['radiology_order_id']);
            $table->index(['radiologist_id']);
            $table->index(['reported_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('radiology_results');
    }
};
