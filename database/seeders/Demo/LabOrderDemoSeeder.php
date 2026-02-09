<?php

declare(strict_types=1);

namespace Database\Seeders\Demo;

use App\Models\Clinical\LaboratoryOrder;
use App\Models\MasterData\Employee;
use App\Models\Patient\Visit;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Demo Laboratory Order Seeder.
 *
 * Creates laboratory orders with:
 * - Various test types
 * - Results entered for completed orders
 * - Validated orders
 * - Normal and abnormal results
 *
 * @package Database\Seeders\Demo
 */
class LabOrderDemoSeeder extends Seeder
{
    /**
     * Laboratory test types.
     *
     * @var array
     */
    protected array $testTypes = [
        ['code' => 'HEMO', 'name' => 'Hemoglobin', 'unit' => 'g/dL', 'normal_range' => '12-16'],
        ['code' => 'LEUKO', 'name' => 'Leukosit', 'unit' => '/uL', 'normal_range' => '4000-11000'],
        ['code' => 'TROMBO', 'name' => 'Trombosit', 'unit' => '/uL', 'normal_range' => '150000-450000'],
        ['code' => 'HT', 'name' => 'Hematokrit', 'unit' => '%', 'normal_range' => '36-48'],
        ['code' => 'GDS', 'name' => 'Gula Darah Sewaktu', 'unit' => 'mg/dL', 'normal_range' => '< 200'],
        ['code' => 'GDP', 'name' => 'Gula Darah Puasa', 'unit' => 'mg/dL', 'normal_range' => '70-100'],
        ['code' => 'GD2PP', 'name' => 'Gula Darah 2 Jam PP', 'unit' => 'mg/dL', 'normal_range' => '< 140'],
        ['code' => 'UREUM', 'name' => 'Ureum', 'unit' => 'mg/dL', 'normal_range' => '10-50'],
        ['code' => 'KREAT', 'name' => 'Kreatinin', 'unit' => 'mg/dL', 'normal_range' => '0.6-1.3'],
        ['code' => 'SGOT', 'name' => 'SGOT/AST', 'unit' => 'U/L', 'normal_range' => '< 40'],
        ['code' => 'SGPT', 'name' => 'SGPT/ALT', 'unit' => 'U/L', 'normal_range' => '< 41'],
        ['code' => 'HBSAG', 'name' => 'HBsAg', 'unit' => '', 'normal_range' => 'Negatif'],
        ['code' => 'WIDAL', 'name' => 'Widal', 'unit' => '', 'normal_range' => 'Negatif'],
        ['code' => 'GOLDA', 'name' => 'Golongan Darah ABO', 'unit' => '', 'normal_range' => 'A/B/AB/O'],
        ['code' => 'RHESUS', 'name' => 'Rhesus', 'unit' => '', 'normal_range' => 'Positif/Negatif'],
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $visits = Visit::all();
        $doctors = Employee::where('is_doctor', true)->get();

        if ($visits->isEmpty()) {
            $this->command->warn('  ! No visits found. Skipping lab order seeding.');
            return;
        }

        $orders = [];
        $results = [];
        $now = now();
        $resultIndex = 1;

        // Create lab orders for visits (40% chance)
        foreach ($visits as $index => $visit) {
            if (rand(0, 100) > 40) {
                continue;
            }

            $order = $this->generateOrderData($index, $visit, $doctors, $now);
            $orders[] = $order;

            // Generate results for completed/validated orders
            if (in_array($order['status'], ['completed', 'validated'])) {
                $testCount = rand(2, 6);
                for ($i = 0; $i < $testCount; $i++) {
                    $results[] = $this->generateResultData($resultIndex++, $order['order_number']);
                }
            }
        }

        // Insert orders
        foreach (array_chunk($orders, 50) as $chunk) {
            DB::table('laboratory_orders')->insert($chunk);
        }

        // Insert results
        if (!empty($results)) {
            foreach (array_chunk($results, 50) as $chunk) {
                DB::table('laboratory_results')->insert($chunk);
            }
        }

        $this->command->info('  ✓ Created ' . count($orders) . ' demo lab orders');
    }

    /**
     * Generate order data.
     *
     * @param int $index
     * @param Visit $visit
     * @param $doctors
     * @param Carbon $now
     * @return array
     */
    protected function generateOrderData(int $index, Visit $visit, $doctors, Carbon $now): array
    {
        $status = $this->getRandomStatus();
        $doctorId = $doctors->isNotEmpty() ? $doctors->random()->id : $visit->doctor_id;
        $isCito = rand(0, 100) > 85;

        return [
            'order_number' => $this->generateOrderNumber($index),
            'visit_id' => $visit->id,
            'patient_id' => $visit->patient_id,
            'doctor_id' => $doctorId,
            'medical_record_id' => null,
            'order_date' => $visit->visit_date->copy()->addMinutes(rand(5, 30)),
            'priority' => $isCito ? 'cito' : $this->getRandomPriority(),
            'status' => $status,
            'diagnosis_notes' => rand(0, 100) > 70 ? 'Pemeriksaan untuk menunjang diagnosis' : null,
            'clinical_notes' => $visit->complaint,
            'total_price' => rand(50000, 500000),
            'is_cito' => $isCito,
            'created_at' => $visit->visit_date,
            'updated_at' => $visit->visit_date,
            'created_by' => $doctorId ?? 1,
            'updated_by' => $doctorId ?? 1,
        ];
    }

    /**
     * Generate result data.
     *
     * @param int $index
     * @param string $orderNumber
     * @return array
     */
    protected function generateResultData(int $index, string $orderNumber): array
    {
        $test = $this->testTypes[array_rand($this->testTypes)];

        return [
            'order_number' => $orderNumber,
            'test_code' => $test['code'],
            'test_name' => $test['name'],
            'result_value' => $this->generateResultValue($test),
            'unit' => $test['unit'],
            'normal_range' => $test['normal_range'],
            'is_abnormal' => rand(0, 100) > 80,
            'notes' => rand(0, 100) > 90 ? 'Perlu pemeriksaan ulang' : null,
            'validated_by' => rand(0, 100) > 30 ? rand(1, 5) : null,
            'validated_at' => rand(0, 100) > 30 ? now() : null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Generate result value based on test type.
     *
     * @param array $test
     * @return string
     */
    protected function generateResultValue(array $test): string
    {
        return match ($test['code']) {
            'HEMO' => (string) rand(110, 180) / 10,
            'LEUKO' => (string) rand(3000, 15000),
            'TROMBO' => (string) rand(100000, 500000),
            'HT' => (string) rand(30, 55),
            'GDS', 'GD2PP' => (string) rand(80, 300),
            'GDP' => (string) rand(70, 180),
            'UREUM' => (string) rand(5, 80),
            'KREAT' => (string) rand(5, 30) / 10,
            'SGOT', 'SGPT' => (string) rand(10, 150),
            'HBSAG' => rand(0, 100) > 90 ? 'Reaktif' : 'Non-Reaktif',
            'WIDAL' => rand(0, 100) > 85 ? 'Positif' : 'Negatif',
            'GOLDA' => ['A', 'B', 'AB', 'O'][array_rand(['A', 'B', 'AB', 'O'])],
            'RHESUS' => rand(0, 100) > 85 ? 'Negatif' : 'Positif',
            default => (string) rand(1, 100),
        };
    }

    /**
     * Generate order number.
     *
     * @param int $index
     * @return string
     */
    protected function generateOrderNumber(int $index): string
    {
        return 'LAB-' . date('Y') . '-' . str_pad((string) ($index + 1), 5, '0', STR_PAD_LEFT);
    }

    /**
     * Get random status.
     *
     * @return string
     */
    protected function getRandomStatus(): string
    {
        $statuses = [
            'pending' => 15,
            'in_progress' => 20,
            'completed' => 45,
            'validated' => 20,
        ];

        return $this->weightedRandom($statuses);
    }

    /**
     * Get random priority.
     *
     * @return string
     */
    protected function getRandomPriority(): string
    {
        $priorities = [
            'normal' => 70,
            'urgent' => 25,
            'cito' => 5,
        ];

        return $this->weightedRandom($priorities);
    }

    /**
     * Get weighted random value.
     *
     * @param array $items
     * @return string
     */
    protected function weightedRandom(array $items): string
    {
        $random = rand(1, 100);
        $cumulative = 0;

        foreach ($items as $item => $probability) {
            $cumulative += $probability;
            if ($random <= $cumulative) {
                return $item;
            }
        }

        return array_key_first($items);
    }
}
