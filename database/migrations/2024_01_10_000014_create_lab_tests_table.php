<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_tests', function (Blueprint $table) {
            $table->id();
            $table->string('test_code', 20)->unique();
            $table->string('name', 255);
            $table->enum('category', ['hematologi', 'kimia_darah', 'urinalisa', 'mikrobiologi', 'imunologi', 'serologi', 'endokrinologi', 'tumor_marker', 'elektrolit', 'gula_darah', 'fungsi_ginjal', 'fungsi_hati', 'lemak_darah', 'koagulasi', 'gas_darah', 'sitologi', 'patologi_anatomi', 'molekuler', 'lainnya']);
            $table->enum('specimen_type', ['darah', 'urine', 'feses', 'sputum', 'lendir', 'jaringan', 'cairan_serebrospinal', 'cairan_sendi', 'cairan_pleura', 'swab', 'lainnya']);
            $table->text('reference_value')->nullable()->comment('Nilai rujukan/normal');
            $table->string('unit', 50)->nullable();
            $table->decimal('base_price', 15, 2)->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['test_code']);
            $table->index(['name']);
            $table->index(['category']);
            $table->index(['specimen_type']);
            $table->index(['is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_tests');
    }
};
