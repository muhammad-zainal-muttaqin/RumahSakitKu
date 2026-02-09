<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->string('user_type', 50)->nullable()->comment('Tipe user: admin, dokter, perawat, dll');
            $table->foreignId('patient_id')->nullable()->constrained();
            $table->string('auditable_type', 100)->comment('Model yang diaudit');
            $table->unsignedBigInteger('auditable_id')->comment('ID record yang diaudit');
            $table->enum('event', ['created', 'updated', 'deleted', 'restored', 'force_deleted']);
            $table->longText('old_values')->nullable()->comment('Nilai lama (JSON)');
            $table->longText('new_values')->nullable()->comment('Nilai baru (JSON)');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('url', 500)->nullable();
            $table->timestamp('created_at');

            // Indexes
            $table->index(['user_id']);
            $table->index(['patient_id']);
            $table->index(['auditable_type', 'auditable_id']);
            $table->index(['event']);
            $table->index(['created_at']);
            $table->index(['ip_address']);

            // Composite indexes for common queries
            $table->index(['auditable_type', 'event', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });

        // Create partition for audit_logs by month (MySQL 8.0+)
        // Note: For PostgreSQL, use declarative partitioning instead
        // This is a basic structure - actual partitioning implementation may vary by database
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
