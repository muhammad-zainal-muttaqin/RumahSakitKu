<?php

declare(strict_types=1);

namespace Database\Seeders;

use Database\Seeders\Demo\InpatientDemoSeeder;
use Database\Seeders\Demo\InvoiceDemoSeeder;
use Database\Seeders\Demo\LabOrderDemoSeeder;
use Database\Seeders\Demo\MedicalRecordDemoSeeder;
use Database\Seeders\Demo\PatientDemoSeeder;
use Database\Seeders\Demo\PrescriptionDemoSeeder;
use Database\Seeders\Demo\SurgeryDemoSeeder;
use Database\Seeders\Demo\VisitDemoSeeder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Main Demo Data Seeder.
 *
 * This seeder orchestrates the creation of all demo data for the SIMRS application.
 * It calls individual seeders in the correct order to maintain data integrity.
 *
 * The seeders are run in the following order:
 * 1. Patients (100 sample patients)
 * 2. Visits (200 sample visits)
 * 3. Medical Records (SOAP format with ICD10)
 * 4. Prescriptions (various medicines and statuses)
 * 5. Inpatients (20 admitted patients)
 * 6. Lab Orders (laboratory test orders)
 * 7. Surgeries (scheduled and completed surgeries)
 * 8. Invoices (payments and billing)
 *
 * @package Database\Seeders
 */
class DemoDataSeeder extends Seeder
{
    /**
     * Run the database seeders.
     *
     * @return void
     */
    public function run(): void
    {
        $this->command->info('Starting demo data seeding...');
        $this->command->info('============================');

        // Disable foreign key checks for faster seeding
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        $startTime = microtime(true);

        // Run seeders in order
        $this->callWithProgress('Patients', PatientDemoSeeder::class);
        $this->callWithProgress('Visits', VisitDemoSeeder::class);
        $this->callWithProgress('Medical Records', MedicalRecordDemoSeeder::class);
        $this->callWithProgress('Prescriptions', PrescriptionDemoSeeder::class);
        $this->callWithProgress('Inpatients', InpatientDemoSeeder::class);
        $this->callWithProgress('Lab Orders', LabOrderDemoSeeder::class);
        $this->callWithProgress('Surgeries', SurgeryDemoSeeder::class);
        $this->callWithProgress('Invoices', InvoiceDemoSeeder::class);

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $endTime = microtime(true);
        $duration = round($endTime - $startTime, 2);

        $this->command->info('============================');
        $this->command->info("Demo data seeding completed in {$duration} seconds!");
        $this->command->info('');
        $this->printSummary();
    }

    /**
     * Call a seeder with progress indication.
     *
     * @param string $name
     * @param string $seederClass
     * @return void
     */
    protected function callWithProgress(string $name, string $seederClass): void
    {
        $this->command->info("Seeding {$name}...");
        $this->call($seederClass);
    }

    /**
     * Print summary of seeded data.
     *
     * @return void
     */
    protected function printSummary(): void
    {
        $this->command->info('Demo Data Summary:');
        $this->command->info('------------------');
        $this->command->info('• 100 Patients with various demographics');
        $this->command->info('• 200 Visits (last 30 days, multiple types)');
        $this->command->info('• Medical Records with SOAP format and ICD10');
        $this->command->info('• Prescriptions with various medicines');
        $this->command->info('• 20 Inpatient admissions');
        $this->command->info('• Laboratory orders with results');
        $this->command->info('• Surgery schedules and procedures');
        $this->command->info('• Invoices and payment records');
        $this->command->info('');
        $this->command->info('Use these credentials for testing:');
        $this->command->info('• Login with your existing user account');
        $this->command->info('• All patients have BPJS and non-BPJS insurance types');
        $this->command->info('• Sample NIK: Search any patient by name or generated NIK');
    }
}
