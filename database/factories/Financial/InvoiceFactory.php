<?php

declare(strict_types=1);

namespace Database\Factories\Financial;

use App\Models\Financial\Invoice;
use App\Models\Patient\Patient;
use App\Models\Patient\Visit;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        $totalAmount = $this->faker->randomFloat(2, 100000, 5000000);
        $discountAmount = $this->faker->randomFloat(2, 0, $totalAmount * 0.1);
        $taxAmount = ($totalAmount - $discountAmount) * 0.11;
        $paidAmount = $this->faker->randomFloat(2, 0, $totalAmount);
        $remainingAmount = $totalAmount - $discountAmount + $taxAmount - $paidAmount;

        return [
            'invoice_number' => 'INV' . $this->faker->unique()->numerify('########'),
            'visit_id' => Visit::factory(),
            'patient_id' => Patient::factory(),
            'invoice_type' => $this->faker->randomElement(['rawat_jalan', 'rawat_inap']),
            'total_amount' => $totalAmount,
            'discount_amount' => $discountAmount,
            'tax_amount' => $taxAmount,
            'paid_amount' => $paidAmount,
            'remaining_amount' => $remainingAmount,
            'status' => $this->faker->randomElement(['draft', 'pending', 'partial', 'paid', 'cancelled']),
            'due_date' => $this->faker->dateTimeBetween('today', '+30 days')->format('Y-m-d'),
            'notes' => $this->faker->optional()->paragraph(),
        ];
    }

    public function paid(): static
    {
        return $this->state(function (array $attributes) {
            $total = $attributes['total_amount'] - $attributes['discount_amount'] + $attributes['tax_amount'];

            return [
                'paid_amount' => $total,
                'remaining_amount' => 0,
                'status' => 'paid',
            ];
        });
    }

    public function partial(float $paidAmount): static
    {
        return $this->state(function (array $attributes) use ($paidAmount) {
            $total = $attributes['total_amount'] - $attributes['discount_amount'] + $attributes['tax_amount'];

            return [
                'paid_amount' => $paidAmount,
                'remaining_amount' => $total - $paidAmount,
                'status' => 'partial',
            ];
        });
    }

    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'due_date' => $this->faker->dateTimeBetween('-30 days', '-1 day')->format('Y-m-d'),
            'status' => 'pending',
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'paid_amount' => 0,
            'remaining_amount' => $attributes['total_amount'] - $attributes['discount_amount'] + $attributes['tax_amount'],
            'status' => 'pending',
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
        ]);
    }

    public function today(): static
    {
        return $this->state(fn (array $attributes) => [
            'created_at' => now(),
        ]);
    }
}
