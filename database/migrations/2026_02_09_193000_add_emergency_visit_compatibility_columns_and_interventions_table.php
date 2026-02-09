<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('visits')) {
            Schema::table('visits', function (Blueprint $table): void {
                if (!Schema::hasColumn('visits', 'complaint')) {
                    $table->text('complaint')->nullable();
                }
                if (!Schema::hasColumn('visits', 'arrival_method')) {
                    $table->string('arrival_method', 50)->nullable();
                }
                if (!Schema::hasColumn('visits', 'transfer_status')) {
                    $table->string('transfer_status', 50)->nullable();
                }
                if (!Schema::hasColumn('visits', 'discharge_condition')) {
                    $table->string('discharge_condition', 100)->nullable();
                }
                if (!Schema::hasColumn('visits', 'final_diagnosis')) {
                    $table->text('final_diagnosis')->nullable();
                }
                if (!Schema::hasColumn('visits', 'home_medications')) {
                    $table->text('home_medications')->nullable();
                }
                if (!Schema::hasColumn('visits', 'follow_up_instructions')) {
                    $table->text('follow_up_instructions')->nullable();
                }
            });
        }

        if (!Schema::hasTable('emergency_interventions')) {
            Schema::create('emergency_interventions', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('visit_id')->constrained('visits')->cascadeOnDelete();
                $table->string('intervention_type', 100);
                $table->text('detail')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('emergency_interventions')) {
            Schema::dropIfExists('emergency_interventions');
        }

        if (Schema::hasTable('visits')) {
            Schema::table('visits', function (Blueprint $table): void {
                foreach ([
                    'complaint',
                    'arrival_method',
                    'transfer_status',
                    'discharge_condition',
                    'final_diagnosis',
                    'home_medications',
                    'follow_up_instructions',
                ] as $column) {
                    if (Schema::hasColumn('visits', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
