<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('assessments', 'vital_signs')) {
            Schema::table('assessments', function (Blueprint $table) {
                $table->json('vital_signs')->nullable()->after('assessed_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('assessments', 'vital_signs')) {
            Schema::table('assessments', function (Blueprint $table) {
                $table->dropColumn('vital_signs');
            });
        }
    }
};

