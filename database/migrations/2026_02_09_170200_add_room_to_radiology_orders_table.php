<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('radiology_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('radiology_orders', 'room')) {
                $table->string('room', 100)->nullable()->after('scheduled_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('radiology_orders', function (Blueprint $table) {
            if (Schema::hasColumn('radiology_orders', 'room')) {
                $table->dropColumn('room');
            }
        });
    }
};
