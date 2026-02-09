<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('satusehat_logs', function (Blueprint $table) {
            $table->id();
            $table->string('resource_type', 50);
            $table->string('fhir_id', 191)->nullable();

            $table->string('local_type', 191)->nullable();
            $table->unsignedBigInteger('local_id')->nullable();

            $table->string('action', 20);
            $table->json('request_data')->nullable();
            $table->json('response_data')->nullable();

            $table->enum('status', ['pending', 'success', 'failed'])->default('pending');
            $table->text('error_message')->nullable();

            $table->integer('response_time_ms')->nullable();
            $table->integer('retry_count')->default(0);

            $table->timestamps();

            $table->index(['resource_type']);
            $table->index(['fhir_id']);
            $table->index(['local_type', 'local_id']);
            $table->index(['status']);
            $table->index(['created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('satusehat_logs');
    }
};
