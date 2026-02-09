<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('surgery_implants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('surgery_id')->constrained();
            $table->string('implant_name', 191);
            $table->string('implant_type', 50);

            $table->string('serial_number', 100)->nullable();
            $table->string('batch_number', 100)->nullable();
            $table->string('manufacturer', 191)->nullable();

            $table->integer('quantity')->default(1);
            $table->string('unit', 20)->default('pcs');
            $table->date('expiry_date')->nullable();
            $table->text('notes')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['surgery_id']);
            $table->index(['implant_type']);
            $table->index(['serial_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('surgery_implants');
    }
};
