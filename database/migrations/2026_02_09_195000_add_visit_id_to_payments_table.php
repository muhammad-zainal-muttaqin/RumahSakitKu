<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('payments', 'visit_id')) {
            Schema::table('payments', function (Blueprint $table): void {
                $table->unsignedBigInteger('visit_id')->nullable()->after('invoice_id');
                $table->index('visit_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('payments', 'visit_id')) {
            Schema::table('payments', function (Blueprint $table): void {
                $table->dropIndex(['visit_id']);
                $table->dropColumn('visit_id');
            });
        }
    }
};
