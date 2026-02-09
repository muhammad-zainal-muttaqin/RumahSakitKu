<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bpjs_logs', function (Blueprint $table) {
            $table->id();
            $table->string('service_type', 50)->comment('Jenis layanan BPJS: sep, rujukan, monitoring, dll');
            $table->string('endpoint', 255)->comment('Endpoint API yang diakses');
            $table->string('method', 10)->default('GET')->comment('HTTP method');
            $table->longText('request_data')->nullable()->comment('Data request (encrypted/json)');
            $table->longText('response_data')->nullable()->comment('Data response (encrypted/json)');
            $table->integer('http_status')->nullable()->comment('HTTP status code');
            $table->text('error_message')->nullable()->comment('Pesan error jika ada');
            $table->decimal('execution_time_ms', 10, 2)->nullable()->comment('Waktu eksekusi dalam ms');
            $table->timestamp('executed_at');
            $table->foreignId('user_id')->nullable()->constrained();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['service_type']);
            $table->index(['endpoint']);
            $table->index(['http_status']);
            $table->index(['executed_at']);
            $table->index(['user_id']);
            $table->index(['service_type', 'executed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bpjs_logs');
    }
};
