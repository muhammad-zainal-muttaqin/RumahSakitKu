<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('visits')) {
            return;
        }

        Schema::table('visits', function (Blueprint $table): void {
            if (!Schema::hasColumn('visits', 'inpatient_status')) {
                $table->string('inpatient_status', 50)->nullable();
            }
            if (!Schema::hasColumn('visits', 'transfer_reason')) {
                $table->text('transfer_reason')->nullable();
            }
            if (!Schema::hasColumn('visits', 'transferred_at')) {
                $table->timestamp('transferred_at')->nullable();
            }
            if (!Schema::hasColumn('visits', 'is_completed')) {
                $table->boolean('is_completed')->default(false);
            }
            if (!Schema::hasColumn('visits', 'discharge_diagnosis')) {
                $table->text('discharge_diagnosis')->nullable();
            }
            if (!Schema::hasColumn('visits', 'discharge_notes')) {
                $table->text('discharge_notes')->nullable();
            }
            if (!Schema::hasColumn('visits', 'is_inpatient')) {
                $table->boolean('is_inpatient')->default(false);
            }
        });
    }

    public function down(): void
    {
        // Compatibility migration only; keep rollback intentionally minimal.
    }
};
