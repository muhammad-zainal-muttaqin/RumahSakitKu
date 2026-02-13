<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medical_records', function (Blueprint $table): void {
            $table->index(['patient_id', 'visit_date'], 'medical_records_patient_visit_idx');
            $table->index('visit_id', 'medical_records_visit_idx');
            $table->index(['is_finalized', 'visit_date'], 'medical_records_finalized_date_idx');
            $table->index('icd10_code', 'medical_records_icd10_idx');
        });

        Schema::table('prescriptions', function (Blueprint $table): void {
            $table->index(['patient_id', 'prescription_date'], 'prescriptions_patient_date_idx');
            $table->index('visit_id', 'prescriptions_visit_idx');
            $table->index(['status', 'prescription_date'], 'prescriptions_status_date_idx');
        });

        Schema::table('laboratory_orders', function (Blueprint $table): void {
            $table->index(['patient_id', 'order_date'], 'laboratory_orders_patient_date_idx');
            $table->index('visit_id', 'laboratory_orders_visit_idx');
            $table->index(['status', 'order_date'], 'laboratory_orders_status_date_idx');
        });

        Schema::table('radiology_orders', function (Blueprint $table): void {
            $table->index(['patient_id', 'order_date'], 'radiology_orders_patient_date_idx');
            $table->index('visit_id', 'radiology_orders_visit_idx');
            $table->index(['status', 'order_date'], 'radiology_orders_status_date_idx');
        });

        Schema::table('payments', function (Blueprint $table): void {
            $table->index(['invoice_id', 'payment_date'], 'payments_invoice_date_idx');
            $table->index(['patient_id', 'payment_date'], 'payments_patient_date_idx');
        });

        Schema::table('assessments', function (Blueprint $table): void {
            $table->index(['patient_id', 'assessed_at'], 'assessments_patient_date_idx');
            $table->index('visit_id', 'assessments_visit_idx');
        });

        Schema::table('invoices', function (Blueprint $table): void {
            $table->index(['patient_id', 'invoice_date'], 'invoices_patient_date_idx');
            $table->index(['status', 'invoice_date'], 'invoices_status_date_idx');
        });

        Schema::table('cppts', function (Blueprint $table): void {
            $table->index('medical_record_id', 'cppts_medical_record_idx');
            $table->index(['patient_id', 'created_at'], 'cppts_patient_created_idx');
        });
    }

    public function down(): void
    {
        Schema::table('medical_records', function (Blueprint $table): void {
            $table->dropIndex('medical_records_patient_visit_idx');
            $table->dropIndex('medical_records_visit_idx');
            $table->dropIndex('medical_records_finalized_date_idx');
            $table->dropIndex('medical_records_icd10_idx');
        });

        Schema::table('prescriptions', function (Blueprint $table): void {
            $table->dropIndex('prescriptions_patient_date_idx');
            $table->dropIndex('prescriptions_visit_idx');
            $table->dropIndex('prescriptions_status_date_idx');
        });

        Schema::table('laboratory_orders', function (Blueprint $table): void {
            $table->dropIndex('laboratory_orders_patient_date_idx');
            $table->dropIndex('laboratory_orders_visit_idx');
            $table->dropIndex('laboratory_orders_status_date_idx');
        });

        Schema::table('radiology_orders', function (Blueprint $table): void {
            $table->dropIndex('radiology_orders_patient_date_idx');
            $table->dropIndex('radiology_orders_visit_idx');
            $table->dropIndex('radiology_orders_status_date_idx');
        });

        Schema::table('payments', function (Blueprint $table): void {
            $table->dropIndex('payments_invoice_date_idx');
            $table->dropIndex('payments_patient_date_idx');
        });

        Schema::table('assessments', function (Blueprint $table): void {
            $table->dropIndex('assessments_patient_date_idx');
            $table->dropIndex('assessments_visit_idx');
        });

        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropIndex('invoices_patient_date_idx');
            $table->dropIndex('invoices_status_date_idx');
        });

        Schema::table('cppts', function (Blueprint $table): void {
            $table->dropIndex('cppts_medical_record_idx');
            $table->dropIndex('cppts_patient_created_idx');
        });
    }
};
