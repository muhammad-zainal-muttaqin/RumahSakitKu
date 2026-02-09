<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medicines', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 255);
            $table->enum('classification', ['obat_bebas', 'obat_bebas_terbatas', 'obat_keras', 'narkotika', 'psikotropik']);
            $table->enum('dosage_form', ['tablet', 'kapsul', 'sirup', 'injeksi', 'salep', 'krim', 'gel', 'tetes', 'inhaler', 'supositoria', 'suspensi', 'eliksir', 'serbuk', 'patch']);
            $table->string('unit', 50);
            $table->string('manufacturer', 150)->nullable();
            $table->string('registration_number', 50)->nullable()->comment('Nomor registrasi BPOM');
            $table->boolean('is_generic')->default(false);
            $table->decimal('stock', 15, 2)->default(0);
            $table->decimal('min_stock', 15, 2)->default(0);
            $table->decimal('selling_price', 15, 2)->default(0);
            $table->decimal('purchase_price', 15, 2)->default(0);
            $table->date('expired_date')->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['code']);
            $table->index(['name']);
            $table->index(['classification']);
            $table->index(['dosage_form']);
            $table->index(['is_active']);
            $table->index(['expired_date']);
            $table->index(['stock', 'min_stock']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medicines');
    }
};
