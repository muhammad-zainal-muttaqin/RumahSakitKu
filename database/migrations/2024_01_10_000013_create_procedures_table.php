<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('procedures', function (Blueprint $table) {
            $table->id();
            $table->string('procedure_code', 20)->unique();
            $table->string('name', 255);
            $table->foreignId('category_id')->constrained('procedure_categories');
            $table->decimal('base_price', 15, 2)->default(0);
            $table->decimal('bpjs_tariff', 15, 2)->nullable()->comment('Tarif BPJS');
            $table->decimal('material_cost', 15, 2)->default(0)->comment('Biaya bahan/material');
            $table->boolean('is_bpjs_covered')->default(true);
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['procedure_code']);
            $table->index(['name']);
            $table->index(['category_id']);
            $table->index(['is_active']);
            $table->index(['is_bpjs_covered']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('procedures');
    }
};
