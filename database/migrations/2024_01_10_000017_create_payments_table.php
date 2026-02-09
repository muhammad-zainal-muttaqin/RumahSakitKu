<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained();
            $table->string('payment_number', 30)->unique();
            $table->date('payment_date')->nullable();
            $table->time('payment_time')->nullable();
            $table->decimal('amount', 15, 2);
            $table->enum('payment_method', ['cash', 'credit_card', 'debit_card', 'bank_transfer', 'bpjs', 'asuransi', 'qris']);
            $table->string('payment_type', 50)->nullable();
            $table->string('reference_number', 100)->nullable()->comment('Nomor referensi/kode bayar');
            $table->string('bank_name', 100)->nullable();
            $table->string('account_number', 50)->nullable();
            $table->string('account_holder', 100)->nullable();
            $table->string('card_number', 50)->nullable();
            $table->string('card_type', 50)->nullable();
            $table->string('approval_code', 50)->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->boolean('is_refunded')->default(false);
            $table->decimal('refunded_amount', 15, 2)->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->text('refund_reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['invoice_id']);
            $table->index(['payment_number']);
            $table->index(['payment_method']);
            $table->index(['reference_number']);
            $table->index(['received_by']);
            $table->index(['paid_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
