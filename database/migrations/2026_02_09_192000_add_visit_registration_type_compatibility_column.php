<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('visits', 'registration_type')) {
            Schema::table('visits', function (Blueprint $table): void {
                $table->string('registration_type', 30)->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('visits', 'registration_type')) {
            Schema::table('visits', function (Blueprint $table): void {
                $table->dropColumn('registration_type');
            });
        }
    }
};
