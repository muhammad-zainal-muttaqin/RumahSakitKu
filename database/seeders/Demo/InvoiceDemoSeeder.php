<?php

declare(strict_types=1);

namespace Database\Seeders\Demo;

use App\Models\Financial\Invoice;
use App\Models\Financial\Payment;
use App\Models\Patient\Patient;
use App\Models\Patient\Visit;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Demo Invoice Seeder.
 *
 * Creates invoices and payments with:
 * - Various amounts
 * - Different payment methods
 * - Paid and pending statuses
 * - Insurance claims
 *
 * @package Database\Seeders\Demo
 */
class InvoiceDemoSeeder extends Seeder
{
    /**
     * Service items for invoice details.
     *
     * @var array
     */
    protected array $serviceItems = [
        ['name' => 'Jasa Dokter', 'price_range' => [50000, 200000]],
        ['name' => 'Jasa Perawat', 'price_range' => [25000, 100000]],
        ['name' => 'Pemeriksaan Laboratorium', 'price_range' => [50000, 500000]],
        ['name' => 'Pemeriksaan Radiologi', 'price_range' => [100000, 800000]],
        ['name' => 'Obat-obatan', 'price_range' => [25000, 500000]],
        ['name' => 'Tindakan Medis', 'price_range' => [50000, 1000000]],
        ['name' => 'Rawat Inap Kelas III', 'price_range' => [150000, 300000]],
        ['name' => 'Rawat Inap Kelas II', 'price_range' => [300000, 600000]],
        ['name' => 'Rawat Inap Kelas I', 'price_range' => [600000, 1200000]],
        ['name' => 'Rawat Inap VIP', 'price_range' => [1500000, 5000000]],
        ['name' => 'Kamar Operasi', 'price_range' => [500000, 3000000]],
        ['name' => 'Anestesi', 'price_range' => [300000, 1500000]],
        ['name' => 'Alat Kesehatan', 'price_range' => [25000, 500000]],
        ['name' => 'Konsultasi', 'price_range' => [50000, 300000]],
        ['name' => 'USG', 'price_range' => [100000, 500000]],
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $visits = Visit::all();

        if ($visits->isEmpty()) {
            $this->command->warn('  ! No visits found. Skipping invoice seeding.');
            return;
        }

        $invoices = [];
        $invoiceItems = [];
        $payments = [];
        $now = now();
        $itemIndex = 1;
        $paymentIndex = 1;

        foreach ($visits as $index => $visit) {
            // Skip visits that are just registered
            if ($visit->status === 'registered') {
                continue;
            }

            $invoice = $this->generateInvoiceData($index, $visit, $now);
            $invoices[] = $invoice;

            // Generate invoice items (3-8 items per invoice)
            $itemCount = rand(3, 8);
            for ($i = 0; $i < $itemCount; $i++) {
                $invoiceItems[] = $this->generateInvoiceItemData($itemIndex++, $invoice['invoice_number']);
            }

            // Generate payments for paid invoices
            if ($invoice['status'] === 'paid') {
                $payments[] = $this->generatePaymentData($paymentIndex++, $invoice);
            } elseif ($invoice['status'] === 'partial') {
                // Generate partial payment
                $payments[] = $this->generatePartialPaymentData($paymentIndex++, $invoice);
            }
        }

        // Insert invoices
        foreach (array_chunk($invoices, 50) as $chunk) {
            DB::table('invoices')->insert($chunk);
        }

        // Insert invoice items
        if (!empty($invoiceItems)) {
            foreach (array_chunk($invoiceItems, 50) as $chunk) {
                DB::table('invoice_items')->insert($chunk);
            }
        }

        // Insert payments
        if (!empty($payments)) {
            foreach (array_chunk($payments, 50) as $chunk) {
                DB::table('payments')->insert($chunk);
            }
        }

        $this->command->info('  ✓ Created ' . count($invoices) . ' demo invoices');
    }

    /**
     * Generate invoice data.
     *
     * @param int $index
     * @param Visit $visit
     * @param Carbon $now
     * @return array
     */
    protected function generateInvoiceData(int $index, Visit $visit, Carbon $now): array
    {
        $patient = Patient::find($visit->patient_id);
        $totalAmount = rand(100000, 5000000);
        $discountAmount = rand(0, 100) > 80 ? rand(10000, $totalAmount * 0.1) : 0;
        $taxAmount = $totalAmount * 0.11; // 11% VAT

        $subtotal = $totalAmount - $taxAmount + $discountAmount;
        $status = $this->getRandomStatus($visit->status);

        $paidAmount = match ($status) {
            'paid' => $totalAmount,
            'partial' => intval($totalAmount * (rand(20, 80) / 100)),
            default => 0,
        };

        $balanceDue = $totalAmount - $paidAmount;

        // Insurance handling
        $insuranceClaimAmount = $patient?->insurance_type === 'bpjs' ? intval($totalAmount * 0.9) : null;
        $insurancePaidAmount = $status === 'paid' && $insuranceClaimAmount ? $insuranceClaimAmount : 0;

        return [
            'invoice_number' => $this->generateInvoiceNumber($index),
            'visit_id' => $visit->id,
            'patient_id' => $visit->patient_id,
            'invoice_date' => $visit->visit_date,
            'due_date' => $visit->visit_date->copy()->addDays(7),
            'subtotal' => $subtotal,
            'discount_amount' => $discountAmount,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount,
            'paid_amount' => $paidAmount,
            'balance_due' => $balanceDue,
            'status' => $status,
            'payment_status' => $status,
            'insurance_claim_status' => $patient?->insurance_type === 'bpjs' ? ($status === 'paid' ? 'approved' : 'pending') : null,
            'insurance_claim_amount' => $insuranceClaimAmount,
            'insurance_paid_amount' => $insurancePaidAmount,
            'notes' => rand(0, 100) > 90 ? 'Diskon khusus pasien lama' : null,
            'created_at' => $visit->visit_date,
            'updated_at' => $now,
            'created_by' => 1,
            'updated_by' => 1,
        ];
    }

    /**
     * Generate invoice item data.
     *
     * @param int $index
     * @param string $invoiceNumber
     * @return array
     */
    protected function generateInvoiceItemData(int $index, string $invoiceNumber): array
    {
        $service = $this->serviceItems[array_rand($this->serviceItems)];
        $quantity = rand(1, 5);
        $unitPrice = rand($service['price_range'][0], $service['price_range'][1]);

        return [
            'invoice_number' => $invoiceNumber,
            'item_type' => 'service',
            'item_name' => $service['name'],
            'description' => null,
            'quantity' => $quantity,
            'unit' => 'item',
            'unit_price' => $unitPrice,
            'discount_amount' => 0,
            'total_price' => $quantity * $unitPrice,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Generate payment data.
     *
     * @param int $index
     * @param array $invoice
     * @return array
     */
    protected function generatePaymentData(int $index, array $invoice): array
    {
        $paymentMethod = $this->getRandomPaymentMethod();
        $paymentDate = Carbon::parse($invoice['invoice_date'])->addDays(rand(0, 3));

        return [
            'payment_number' => $this->generatePaymentNumber($index),
            'invoice_number' => $invoice['invoice_number'],
            'payment_date' => $paymentDate,
            'payment_time' => $paymentDate->copy()->setTime(rand(8, 17), rand(0, 59)),
            'amount' => $invoice['total_amount'],
            'payment_method' => $paymentMethod,
            'payment_type' => 'full',
            'reference_number' => in_array($paymentMethod, ['credit_card', 'debit_card', 'bank_transfer']) ? 'REF' . rand(100000, 999999) : null,
            'bank_name' => $paymentMethod === 'bank_transfer' ? $this->getRandomBank() : null,
            'account_number' => null,
            'account_holder' => null,
            'card_number' => in_array($paymentMethod, ['credit_card', 'debit_card']) ? substr(str_shuffle('0123456789'), 0, 4) . '********' . substr(str_shuffle('0123456789'), 0, 4) : null,
            'card_type' => $paymentMethod === 'credit_card' ? ['Visa', 'Mastercard'][array_rand(['Visa', 'Mastercard'])] : null,
            'approval_code' => in_array($paymentMethod, ['credit_card', 'debit_card']) ? strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 6)) : null,
            'received_by' => 'Kasir ' . rand(1, 5),
            'notes' => null,
            'is_refunded' => false,
            'refunded_amount' => null,
            'refunded_at' => null,
            'refund_reason' => null,
            'created_at' => $paymentDate,
            'updated_at' => now(),
            'created_by' => 1,
            'updated_by' => 1,
        ];
    }

    /**
     * Generate partial payment data.
     *
     * @param int $index
     * @param array $invoice
     * @return array
     */
    protected function generatePartialPaymentData(int $index, array $invoice): array
    {
        $payment = $this->generatePaymentData($index, $invoice);
        $payment['amount'] = $invoice['paid_amount'];
        $payment['payment_type'] = 'partial';

        return $payment;
    }

    /**
     * Generate invoice number.
     *
     * @param int $index
     * @return string
     */
    protected function generateInvoiceNumber(int $index): string
    {
        return 'INV-' . date('Y') . '-' . str_pad((string) ($index + 1), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Generate payment number.
     *
     * @param int $index
     * @return string
     */
    protected function generatePaymentNumber(int $index): string
    {
        return 'PAY-' . date('Y') . '-' . str_pad((string) ($index + 1), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Get random status.
     *
     * @param string $visitStatus
     * @return string
     */
    protected function getRandomStatus(string $visitStatus): string
    {
        // If visit is not completed, invoice is likely pending
        if ($visitStatus !== 'completed') {
            return rand(0, 100) > 70 ? 'paid' : 'pending';
        }

        $statuses = [
            'paid' => 70,
            'partial' => 15,
            'pending' => 15,
        ];

        return $this->weightedRandom($statuses);
    }

    /**
     * Get random payment method.
     *
     * @return string
     */
    protected function getRandomPaymentMethod(): string
    {
        $methods = [
            'cash' => 40,
            'credit_card' => 15,
            'debit_card' => 20,
            'bank_transfer' => 15,
            'mobile_payment' => 8,
            'bpjs' => 2,
        ];

        return $this->weightedRandom($methods);
    }

    /**
     * Get random bank name.
     *
     * @return string
     */
    protected function getRandomBank(): string
    {
        $banks = [
            'BCA',
            'Mandiri',
            'BNI',
            'BRI',
            'CIMB Niaga',
            'Permata Bank',
            'Danamon',
            'Maybank',
        ];

        return $banks[array_rand($banks)];
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
