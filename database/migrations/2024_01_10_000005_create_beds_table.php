<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('beds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained();
            $table->string('bed_number', 20);
            $table->string('bed_name', 50)->nullable();
            $table->enum('bed_type', ['standard', 'electric', 'manual', 'baby', 'icu'])->default('standard');
            $table->enum('status', ['kosong', 'terisi', 'reserved', 'maintenance', 'cleaning'])->default('kosong');
            $table->foreignId('current_visit_id')->nullable()->constrained('visits')->nullOnDelete();
            $table->timestamp('occupied_at')->nullable();
            $table->timestamp('vacated_at')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['room_id', 'bed_number']);
            $table->index(['status', 'room_id']);
            $table->index(['current_visit_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beds');
    }
};
