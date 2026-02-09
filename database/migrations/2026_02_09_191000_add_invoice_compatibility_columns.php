<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('invoices')) {
            return;
        }

        Schema::table('invoices', function (Blueprint $table): void {
            if (!Schema::hasColumn('invoices', 'invoice_date')) {
                $table->timestamp('invoice_date')->nullable();
            }
            if (!Schema::hasColumn('invoices', 'subtotal')) {
                $table->decimal('subtotal', 15, 2)->default(0);
            }
            if (!Schema::hasColumn('invoices', 'total')) {
                $table->decimal('total', 15, 2)->default(0);
            }
            if (!Schema::hasColumn('invoices', 'balance_due')) {
                $table->decimal('balance_due', 15, 2)->default(0);
            }
            if (!Schema::hasColumn('invoices', 'payment_status')) {
                $table->string('payment_status', 30)->nullable();
            }
            if (!Schema::hasColumn('invoices', 'insurance_claim_amount')) {
                $table->decimal('insurance_claim_amount', 15, 2)->nullable();
            }
            if (!Schema::hasColumn('invoices', 'insurance_claim_status')) {
                $table->string('insurance_claim_status', 30)->nullable();
            }
        });
    }

    public function down(): void
    {
        // Compatibility migration only; keep rollback intentionally minimal.
    }
};
