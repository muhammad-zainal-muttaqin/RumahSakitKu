<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('polyclinics', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('name', 100);
            $table->enum('category', ['umum', 'spesialis', 'gigi', 'anak', 'bedah', 'penyakit_dalam', 'syaraf', 'jiwa', 'rehabilitasi', 'radiologi', 'laboratorium']);
            $table->string('queue_prefix', 5)->default('A');
            $table->string('bpjs_poli_code', 10)->nullable();
            $table->string('bpjs_poli_name', 100)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('max_queue_per_day')->default(100);
            $table->time('open_time')->default('08:00:00');
            $table->time('close_time')->default('16:00:00');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['code']);
            $table->index(['category', 'is_active']);
            $table->index(['bpjs_poli_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('polyclinics');
    }
};
