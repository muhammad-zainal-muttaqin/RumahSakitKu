<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('assessments', 'triage_level')) {
            Schema::table('assessments', function (Blueprint $table): void {
                $table->string('triage_level', 20)->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('assessments', 'triage_level')) {
            Schema::table('assessments', function (Blueprint $table): void {
                $table->dropColumn('triage_level');
            });
        }
    }
};
