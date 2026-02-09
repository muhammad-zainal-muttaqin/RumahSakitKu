<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('patients', 'satusehat_ihs_number')) {
            Schema::table('patients', function (Blueprint $table) {
                $table->string('satusehat_ihs_number', 100)->nullable()->after('notes');
            });
        }

        if (!Schema::hasColumn('visits', 'satusehat_encounter_id')) {
            Schema::table('visits', function (Blueprint $table) {
                $table->string('satusehat_encounter_id', 100)->nullable()->after('notes');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('patients', 'satusehat_ihs_number')) {
            Schema::table('patients', function (Blueprint $table) {
                $table->dropColumn('satusehat_ihs_number');
            });
        }

        if (Schema::hasColumn('visits', 'satusehat_encounter_id')) {
            Schema::table('visits', function (Blueprint $table) {
                $table->dropColumn('satusehat_encounter_id');
            });
        }
    }
};

