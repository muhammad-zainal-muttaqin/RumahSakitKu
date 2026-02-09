<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 100);
            $table->enum('room_class', ['VVIP', 'VIP', 'Kelas I', 'Kelas II', 'Kelas III', 'ICU', 'NICU', 'PICU', 'HCU']);
            $table->string('floor', 10)->nullable();
            $table->string('building', 50)->nullable();
            $table->string('gender_preference', 10)->nullable()->comment('L/P/Campur');
            $table->integer('total_beds')->default(0);
            $table->integer('available_beds')->default(0);
            $table->decimal('base_price', 15, 2)->default(0);
            $table->decimal('bpjs_price', 15, 2)->default(0);
            $table->text('facilities')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['code']);
            $table->index(['room_class', 'is_active']);
            $table->index(['is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
