<?php

declare(strict_types=1);

namespace Database\Factories\Financial;

use App\Models\Financial\Invoice;
use App\Models\Financial\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'payment_number' => 'PAY' . $this->faker->unique()->numerify('########'),
            'invoice_id' => Invoice::factory(),
            'payment_date' => now()->format('Y-m-d'),
            'payment_time' => now()->format('H:i:s'),
            'amount' => $this->faker->randomFloat(2, 50000, 5000000),
            'payment_method' => $this->faker->randomElement(['cash', 'credit_card', 'debit_card', 'bank_transfer', 'bpjs', 'asuransi', 'qris']),
            'payment_type' => null,
            'reference_number' => $this->faker->optional()->numerify('REF########'),
            'bank_name' => null,
            'account_number' => null,
            'account_holder' => null,
            'card_number' => null,
            'card_type' => null,
            'approval_code' => null,
            'received_by' => User::factory(),
            'notes' => $this->faker->optional()->sentence(),
            'paid_at' => now(),
            'is_refunded' => false,
            'refunded_amount' => null,
            'refunded_at' => null,
            'refund_reason' => null,
            'created_by' => 1,
            'updated_by' => 1,
        ];
    }

    public function cash(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => 'cash',
            'reference_number' => null,
        ]);
    }

    public function card(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => 'card',
        ]);
    }

    public function transfer(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => 'transfer',
        ]);
    }

    public function today(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_date' => now()->format('Y-m-d'),
            'paid_at' => now(),
        ]);
    }

    public function creditCard(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => 'credit_card',
            'bank_name' => $this->faker->randomElement(['BCA', 'BNI', 'BRI', 'Mandiri']),
            'card_number' => $this->faker->numerify('################'),
            'card_type' => $this->faker->randomElement(['Visa', 'Mastercard']),
            'approval_code' => $this->faker->numerify('######'),
        ]);
    }

    public function bankTransfer(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => 'bank_transfer',
            'bank_name' => $this->faker->randomElement(['BCA', 'BNI', 'BRI', 'Mandiri']),
            'account_number' => $this->faker->numerify('##########'),
            'account_holder' => $this->faker->name(),
        ]);
    }

    public function refunded(float $amount = null, string $reason = 'Customer request'): static
    {
        return $this->state(fn (array $attributes) => [
            'is_refunded' => true,
            'refunded_amount' => $amount ?? $attributes['amount'],
            'refunded_at' => now(),
            'refund_reason' => $reason,
        ]);
    }

    public function partialRefund(float $amount): static
    {
        return $this->state(fn (array $attributes) => [
            'is_refunded' => false,
            'refunded_amount' => $amount,
            'refunded_at' => now(),
            'refund_reason' => 'Partial refund',
        ]);
    }
}
