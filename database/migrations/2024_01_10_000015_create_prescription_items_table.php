<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prescription_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prescription_id')->constrained();
            $table->foreignId('medicine_id')->nullable()->constrained()->nullOnDelete();

            // Medicine Info
            $table->string('generic_name');
            $table->string('brand_name')->nullable();
            $table->string('dosage_form', 50)->nullable();
            $table->string('strength', 50)->nullable();

            // Quantity and Dosage
            $table->decimal('quantity', 10, 2);
            $table->string('unit', 50);
            $table->text('dosage_instructions')->nullable()->comment('Instruksi dosis dan cara pakai');
            $table->string('frequency', 100)->nullable();
            $table->integer('duration_days')->nullable();
            $table->string('route_of_administration', 50)->nullable();
            $table->text('instructions')->nullable();

            // Substitution
            $table->boolean('is_substitutable')->default(false);
            $table->text('substitution_notes')->nullable();

            // Pricing
            $table->decimal('unit_price', 15, 2)->nullable();
            $table->decimal('total_price', 15, 2)->nullable();

            // Dispensing
            $table->boolean('is_dispensed')->default(false);
            $table->decimal('dispensed_quantity', 10, 2)->nullable();
            $table->timestamp('dispensed_at')->nullable();

            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['prescription_id']);
            $table->index(['medicine_id']);
            $table->index(['is_dispensed']);
            $table->index(['is_substitutable']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prescription_items');
    }
};
