<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            // Add columns that tests expect but don't exist in original migration
            $table->string('phone', 20)->nullable()->after('phone_primary');
            $table->string('insurance_type', 50)->nullable()->after('insurance_name');
            $table->string('bpjs_card_number', 20)->nullable()->after('bpjs_number');
            $table->string('emergency_contact_name', 100)->nullable()->after('emergency_name');
            $table->string('emergency_contact_phone', 20)->nullable()->after('emergency_phone');
            $table->timestamp('registered_at')->nullable()->after('total_visits');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropColumn([
                'phone',
                'insurance_type',
                'bpjs_card_number',
                'emergency_contact_name',
                'emergency_contact_phone',
                'registered_at',
            ]);
        });
    }
};
