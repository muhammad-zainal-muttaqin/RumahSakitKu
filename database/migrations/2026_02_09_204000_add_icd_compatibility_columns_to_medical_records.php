<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('medical_records', 'icd_code')) {
            Schema::table('medical_records', function (Blueprint $table) {
                $table->string('icd_code', 20)->nullable()->after('icd10_code');
            });
        }

        if (!Schema::hasColumn('medical_records', 'icd_name')) {
            Schema::table('medical_records', function (Blueprint $table) {
                $table->string('icd_name', 255)->nullable()->after('icd10_description');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('medical_records', 'icd_code')) {
            Schema::table('medical_records', function (Blueprint $table) {
                $table->dropColumn('icd_code');
            });
        }

        if (Schema::hasColumn('medical_records', 'icd_name')) {
            Schema::table('medical_records', function (Blueprint $table) {
                $table->dropColumn('icd_name');
            });
        }
    }
};

