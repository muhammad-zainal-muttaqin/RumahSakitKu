<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('medical_records')) {
            Schema::table('medical_records', function (Blueprint $table): void {
                if (! Schema::hasColumn('medical_records', 'visit_date')) {
                    $table->date('visit_date')->nullable();
                }
                if (! Schema::hasColumn('medical_records', 'subjective')) {
                    $table->text('subjective')->nullable();
                }
                if (! Schema::hasColumn('medical_records', 'objective')) {
                    $table->text('objective')->nullable();
                }
                if (! Schema::hasColumn('medical_records', 'assessment')) {
                    $table->text('assessment')->nullable();
                }
                if (! Schema::hasColumn('medical_records', 'plan')) {
                    $table->text('plan')->nullable();
                }
                if (! Schema::hasColumn('medical_records', 'diagnosis_primary')) {
                    $table->string('diagnosis_primary', 255)->nullable();
                }
                if (! Schema::hasColumn('medical_records', 'diagnosis_secondary')) {
                    $table->string('diagnosis_secondary', 255)->nullable();
                }
                if (! Schema::hasColumn('medical_records', 'icd10_code')) {
                    $table->string('icd10_code', 20)->nullable();
                }
                if (! Schema::hasColumn('medical_records', 'icd10_description')) {
                    $table->text('icd10_description')->nullable();
                }
                if (! Schema::hasColumn('medical_records', 'procedure_code')) {
                    $table->string('procedure_code', 50)->nullable();
                }
                if (! Schema::hasColumn('medical_records', 'procedure_description')) {
                    $table->text('procedure_description')->nullable();
                }
                if (! Schema::hasColumn('medical_records', 'is_finalized')) {
                    $table->boolean('is_finalized')->default(false);
                }
                if (! Schema::hasColumn('medical_records', 'finalized_at')) {
                    $table->timestamp('finalized_at')->nullable();
                }
                if (! Schema::hasColumn('medical_records', 'finalized_by')) {
                    $table->unsignedBigInteger('finalized_by')->nullable();
                }
                if (! Schema::hasColumn('medical_records', 'updated_by')) {
                    $table->unsignedBigInteger('updated_by')->nullable();
                }
            });
        }

        if (Schema::hasTable('visits')) {
            Schema::table('visits', function (Blueprint $table): void {
                if (! Schema::hasColumn('visits', 'visit_date')) {
                    $table->timestamp('visit_date')->nullable();
                }
                if (! Schema::hasColumn('visits', 'status')) {
                    $table->string('status', 50)->nullable();
                }
                if (! Schema::hasColumn('visits', 'triage_level')) {
                    $table->string('triage_level', 50)->nullable();
                }
                if (! Schema::hasColumn('visits', 'check_in_at')) {
                    $table->timestamp('check_in_at')->nullable();
                }
                if (! Schema::hasColumn('visits', 'check_out_at')) {
                    $table->timestamp('check_out_at')->nullable();
                }
            });
        }

        if (Schema::hasTable('assessments')) {
            Schema::table('assessments', function (Blueprint $table): void {
                if (! Schema::hasColumn('assessments', 'assessment_type')) {
                    $table->string('assessment_type', 50)->nullable();
                }
                if (! Schema::hasColumn('assessments', 'assessment_date')) {
                    $table->timestamp('assessment_date')->nullable();
                }
            });
        }

        if (Schema::hasTable('procedure_categories')) {
            Schema::table('procedure_categories', function (Blueprint $table): void {
                if (! Schema::hasColumn('procedure_categories', 'color')) {
                    $table->string('color', 20)->nullable();
                }
                if (! Schema::hasColumn('procedure_categories', 'icon')) {
                    $table->string('icon', 100)->nullable();
                }
            });
        }

        if (Schema::hasTable('procedures')) {
            Schema::table('procedures', function (Blueprint $table): void {
                if (! Schema::hasColumn('procedures', 'procedure_category_id')) {
                    $table->unsignedBigInteger('procedure_category_id')->nullable()->after('category_id');
                    $table->index('procedure_category_id');
                }
            });
        }

        if (Schema::hasTable('employees')) {
            Schema::table('employees', function (Blueprint $table): void {
                if (! Schema::hasColumn('employees', 'employee_number')) {
                    $table->string('employee_number', 50)->nullable();
                }
                if (! Schema::hasColumn('employees', 'polyclinic_id')) {
                    $table->unsignedBigInteger('polyclinic_id')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        // Compatibility migration only; keep rollback intentionally minimal.
    }
};
