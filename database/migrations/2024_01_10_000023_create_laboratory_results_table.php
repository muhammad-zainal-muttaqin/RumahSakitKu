<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('laboratory_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('laboratory_order_id')->constrained();
            $table->foreignId('lab_test_id')->nullable()->constrained();

            $table->decimal('result_value', 12, 4)->nullable();
            $table->text('result_text')->nullable();
            $table->enum('flag', ['normal', 'low', 'high', 'abnormal', 'critical'])->nullable();

            $table->string('reference_range', 100)->nullable();
            $table->string('unit', 20)->nullable();
            $table->text('notes')->nullable();

            $table->string('test_method', 100)->nullable();
            $table->string('analyzer_machine', 100)->nullable();

            $table->foreignId('validated_by')->nullable()->constrained('employees');
            $table->timestamp('validated_at')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['laboratory_order_id']);
            $table->index(['lab_test_id']);
            $table->index(['flag']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laboratory_results');
    }
};
