<?php

declare(strict_types=1);

namespace App\Services\Billing;

use RuntimeException;
use Exception;
use App\Models\Financial\Invoice;
use App\Models\Financial\Payment;
use App\Models\Patient\Visit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Billing Service
 *
 * Manages invoice creation, charge calculation, payment processing,
 * refunds, and financial reporting for hospital billing operations.
 */
class BillingService
{
    /**
     * Create an invoice for a visit.
     *
     * @param int $visitId The visit ID
     * @param array $items Optional array of invoice line items
     * @return Invoice The created invoice
     *
     * @throws RuntimeException If the invoice could not be created
     */
    public function createInvoice(int $visitId, array $items = []): Invoice
    {
        try {
            DB::beginTransaction();

            $visit = Visit::with('patient')->find($visitId);

            if (!$visit) {
                DB::rollBack();
                throw new RuntimeException("Visit with ID {$visitId} not found.");
            }

            // Check if an invoice already exists for this visit
            $existingInvoice = Invoice::where('visit_id', $visitId)->first();
            if ($existingInvoice) {
                DB::rollBack();
                throw new RuntimeException("Invoice already exists for visit ID {$visitId}.");
            }

            // Generate invoice number: INV-YYMMDD-XXXX
            $invoiceNumber = $this->generateInvoiceNumber();

            // Calculate totals from items
            $subtotal = 0.0;
            foreach ($items as $item) {
                $subtotal += (float) ($item['amount'] ?? 0) * (int) ($item['quantity'] ?? 1);
            }

            // If no items provided, calculate charges from visit
            if (empty($items)) {
                $charges = $this->calculateVisitCharges($visitId);
                $subtotal = (float) $charges['total'];
            }

            $discountAmount = 0.0;
            $taxAmount = 0.0;
            $totalAmount = $subtotal - $discountAmount + $taxAmount;

            $invoice = Invoice::create([
                'invoice_number' => $invoiceNumber,
                'visit_id' => $visitId,
                'patient_id' => $visit->patient_id,
                'invoice_date' => now(),
                'due_date' => now()->addDays(30),
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'paid_amount' => 0,
                'balance_due' => $totalAmount,
                'status' => 'pending',
                'payment_status' => 'unpaid',
            ]);

            DB::commit();

            Log::info('BillingService: Invoice created successfully', [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoiceNumber,
                'visit_id' => $visitId,
                'total_amount' => $totalAmount,
            ]);

            return $invoice;
        } catch (RuntimeException $e) {
            throw $e;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('BillingService: Error creating invoice', [
                'visit_id' => $visitId,
                'error' => $e->getMessage(),
            ]);
            throw new RuntimeException('Failed to create invoice: ' . $e->getMessage());
        }
    }

    /**
     * Calculate all charges for a visit.
     *
     * Aggregates charges from consultations, procedures, medicines,
     * laboratory tests, radiology, and room charges.
     *
     * @param int $visitId The visit ID
     * @return array Itemized charges with category totals and grand total
     */
    public function calculateVisitCharges(int $visitId): array
    {
        try {
            $visit = Visit::find($visitId);

            if (!$visit) {
                Log::warning('BillingService: Visit not found for charge calculation', [
                    'visit_id' => $visitId,
                ]);
                return [
                    'consultation' => 0.0,
                    'procedures' => 0.0,
                    'medicines' => 0.0,
                    'laboratory' => 0.0,
                    'radiology' => 0.0,
                    'room' => 0.0,
                    'total' => 0.0,
                ];
            }

            // Consultation fee (from doctor/polyclinic)
            $consultation = (float) DB::table('visits')
                ->where('id', $visitId)
                ->value('consultation_fee') ?? 0.0;

            // Procedure/surgery charges
            $procedures = (float) DB::table('surgeries')
                ->where('visit_id', $visitId)
                ->whereNull('deleted_at')
                ->whereNotIn('status', ['cancelled'])
                ->sum('total_price');

            // Medicine charges from prescriptions
            $medicines = (float) DB::table('prescription_items')
                ->join('prescriptions', 'prescriptions.id', '=', 'prescription_items.prescription_id')
                ->where('prescriptions.visit_id', $visitId)
                ->whereNull('prescriptions.deleted_at')
                ->sum('prescription_items.total_price');

            // Laboratory charges
            $laboratory = (float) DB::table('laboratory_orders')
                ->where('visit_id', $visitId)
                ->whereNull('deleted_at')
                ->sum('total_price');

            // Radiology charges
            $radiology = (float) DB::table('radiology_orders')
                ->where('visit_id', $visitId)
                ->whereNull('deleted_at')
                ->sum('total_price');

            // Room charges (for inpatients)
            $room = (float) DB::table('beds')
                ->join('rooms', 'rooms.id', '=', 'beds.room_id')
                ->where('beds.current_visit_id', $visitId)
                ->sum('rooms.base_price');

            $total = $consultation + $procedures + $medicines + $laboratory + $radiology + $room;

            return [
                'consultation' => $consultation,
                'procedures' => $procedures,
                'medicines' => $medicines,
                'laboratory' => $laboratory,
                'radiology' => $radiology,
                'room' => $room,
                'total' => $total,
            ];
        } catch (Exception $e) {
            Log::error('BillingService: Error calculating visit charges', [
                'visit_id' => $visitId,
                'error' => $e->getMessage(),
            ]);

            return [
                'consultation' => 0.0,
                'procedures' => 0.0,
                'medicines' => 0.0,
                'laboratory' => 0.0,
                'radiology' => 0.0,
                'room' => 0.0,
                'total' => 0.0,
            ];
        }
    }

    /**
     * Process a payment against an invoice.
     *
     * @param int $invoiceId The invoice ID
     * @param array $paymentData Payment details (amount, payment_method, reference_number, etc.)
     * @return Payment The created payment record
     *
     * @throws RuntimeException If the payment could not be processed
     */
    public function processPayment(int $invoiceId, array $paymentData): Payment
    {
        try {
            DB::beginTransaction();

            $amount = (float) ($paymentData['amount'] ?? 0);

            if ($amount <= 0) {
                DB::rollBack();
                throw new RuntimeException('Payment amount must be greater than zero.');
            }

            $invoice = Invoice::find($invoiceId);

            if (!$invoice) {
                DB::rollBack();
                throw new RuntimeException("Invoice with ID {$invoiceId} not found.");
            }

            if ($invoice->status === 'paid') {
                DB::rollBack();
                throw new RuntimeException("Invoice {$invoice->invoice_number} is already fully paid.");
            }

            if ($amount > (float) $invoice->balance_due) {
                DB::rollBack();
                throw new RuntimeException('Payment amount exceeds the outstanding balance.');
            }

            // Generate payment number: PAY-YYMMDD-XXXX
            $paymentNumber = $this->generatePaymentNumber();

            $payment = Payment::create([
                'payment_number' => $paymentNumber,
                'invoice_id' => $invoiceId,
                'payment_date' => now()->toDateString(),
                'payment_time' => now(),
                'amount' => $amount,
                'payment_method' => $paymentData['payment_method'] ?? 'cash',
                'payment_type' => $paymentData['payment_type'] ?? 'payment',
                'reference_number' => $paymentData['reference_number'] ?? null,
                'bank_name' => $paymentData['bank_name'] ?? null,
                'account_number' => $paymentData['account_number'] ?? null,
                'account_holder' => $paymentData['account_holder'] ?? null,
                'card_number' => $paymentData['card_number'] ?? null,
                'card_type' => $paymentData['card_type'] ?? null,
                'approval_code' => $paymentData['approval_code'] ?? null,
                'received_by' => $paymentData['received_by'] ?? null,
                'notes' => $paymentData['notes'] ?? null,
                'is_refunded' => false,
            ]);

            // Update invoice paid amount and balance
            $invoice->update([
                'paid_amount' => (float) $invoice->paid_amount + $amount,
            ]);

            DB::commit();

            Log::info('BillingService: Payment processed successfully', [
                'payment_id' => $payment->id,
                'payment_number' => $paymentNumber,
                'invoice_id' => $invoiceId,
                'amount' => $amount,
                'method' => $paymentData['payment_method'] ?? 'cash',
            ]);

            return $payment;
        } catch (RuntimeException $e) {
            throw $e;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('BillingService: Error processing payment', [
                'invoice_id' => $invoiceId,
                'error' => $e->getMessage(),
            ]);
            throw new RuntimeException('Failed to process payment: ' . $e->getMessage());
        }
    }

    /**
     * Process a refund for a payment.
     *
     * @param int $paymentId The original payment ID
     * @param float $amount The refund amount
     * @param string $reason The reason for the refund
     * @return Payment The updated payment record with refund details
     *
     * @throws RuntimeException If the refund could not be processed
     */
    public function processRefund(int $paymentId, float $amount, string $reason): Payment
    {
        try {
            DB::beginTransaction();

            if ($amount <= 0) {
                DB::rollBack();
                throw new RuntimeException('Refund amount must be greater than zero.');
            }

            $payment = Payment::with('invoice')->find($paymentId);

            if (!$payment) {
                DB::rollBack();
                throw new RuntimeException("Payment with ID {$paymentId} not found.");
            }

            if (!$payment->can_be_refunded) {
                DB::rollBack();
                throw new RuntimeException("Payment {$payment->payment_number} cannot be refunded.");
            }

            if ($amount > $payment->refundable_amount) {
                DB::rollBack();
                throw new RuntimeException('Refund amount exceeds the refundable amount.');
            }

            // Process refund on the payment record
            $payment->refund($amount, $reason);

            // Update invoice: reduce paid amount, increase balance
            $invoice = $payment->invoice;
            if ($invoice) {
                $invoice->update([
                    'paid_amount' => max(0, (float) $invoice->paid_amount - $amount),
                ]);
            }

            DB::commit();

            Log::info('BillingService: Refund processed successfully', [
                'payment_id' => $paymentId,
                'refund_amount' => $amount,
                'reason' => $reason,
            ]);

            return $payment->fresh();
        } catch (RuntimeException $e) {
            throw $e;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('BillingService: Error processing refund', [
                'payment_id' => $paymentId,
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);
            throw new RuntimeException('Failed to process refund: ' . $e->getMessage());
        }
    }

    /**
     * Get revenue summary report for a date range.
     *
     * @param string $startDate Start date (Y-m-d)
     * @param string $endDate End date (Y-m-d)
     * @return array Revenue summary including totals by payment method and status
     */
    public function getRevenueReport(string $startDate, string $endDate): array
    {
        try {
            // Total billed
            $invoiceStats = DB::table('invoices')
                ->whereBetween('invoice_date', [$startDate, $endDate])
                ->whereNull('deleted_at')
                ->selectRaw('COUNT(*) as total_invoices')
                ->selectRaw('COALESCE(SUM(total_amount), 0) as total_billed')
                ->selectRaw('COALESCE(SUM(paid_amount), 0) as total_collected')
                ->selectRaw('COALESCE(SUM(balance_due), 0) as total_outstanding')
                ->selectRaw('COALESCE(SUM(discount_amount), 0) as total_discounts')
                ->first();

            // Revenue by payment method
            $byPaymentMethod = DB::table('payments')
                ->whereBetween('payment_date', [$startDate, $endDate])
                ->where('is_refunded', false)
                ->whereNull('deleted_at')
                ->groupBy('payment_method')
                ->selectRaw('payment_method, COUNT(*) as count, COALESCE(SUM(amount), 0) as total')
                ->get()
                ->keyBy('payment_method')
                ->toArray();

            // Total refunds
            $refundStats = DB::table('payments')
                ->whereBetween('payment_date', [$startDate, $endDate])
                ->where('is_refunded', true)
                ->whereNull('deleted_at')
                ->selectRaw('COUNT(*) as refund_count')
                ->selectRaw('COALESCE(SUM(refunded_amount), 0) as total_refunded')
                ->first();

            // Invoice status breakdown
            $byStatus = DB::table('invoices')
                ->whereBetween('invoice_date', [$startDate, $endDate])
                ->whereNull('deleted_at')
                ->groupBy('status')
                ->selectRaw('status, COUNT(*) as count, COALESCE(SUM(total_amount), 0) as total')
                ->get()
                ->keyBy('status')
                ->toArray();

            return [
                'period' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                ],
                'total_invoices' => (int) $invoiceStats->total_invoices,
                'total_billed' => (float) $invoiceStats->total_billed,
                'total_collected' => (float) $invoiceStats->total_collected,
                'total_outstanding' => (float) $invoiceStats->total_outstanding,
                'total_discounts' => (float) $invoiceStats->total_discounts,
                'total_refunded' => (float) $refundStats->total_refunded,
                'refund_count' => (int) $refundStats->refund_count,
                'net_revenue' => (float) $invoiceStats->total_collected - (float) $refundStats->total_refunded,
                'by_payment_method' => $byPaymentMethod,
                'by_status' => $byStatus,
            ];
        } catch (Exception $e) {
            Log::error('BillingService: Error generating revenue report', [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'error' => $e->getMessage(),
            ]);

            return [
                'period' => ['start_date' => $startDate, 'end_date' => $endDate],
                'total_invoices' => 0,
                'total_billed' => 0.0,
                'total_collected' => 0.0,
                'total_outstanding' => 0.0,
                'total_discounts' => 0.0,
                'total_refunded' => 0.0,
                'refund_count' => 0,
                'net_revenue' => 0.0,
                'by_payment_method' => [],
                'by_status' => [],
            ];
        }
    }

    /**
     * Get daily financial reconciliation data.
     *
     * Provides a summary of all financial activity for a given date,
     * including invoices issued, payments received, and refunds processed.
     *
     * @param string $date The date to reconcile (Y-m-d)
     * @return array Daily reconciliation data
     */
    public function getDailyReconciliation(string $date): array
    {
        try {
            // Invoices created on this date
            $invoices = DB::table('invoices')
                ->whereDate('invoice_date', $date)
                ->whereNull('deleted_at')
                ->selectRaw('COUNT(*) as count')
                ->selectRaw('COALESCE(SUM(total_amount), 0) as total_amount')
                ->first();

            // Payments received on this date
            $payments = DB::table('payments')
                ->whereDate('payment_date', $date)
                ->where('is_refunded', false)
                ->whereNull('deleted_at')
                ->selectRaw('COUNT(*) as count')
                ->selectRaw('COALESCE(SUM(amount), 0) as total_amount')
                ->first();

            // Payments by method on this date
            $paymentsByMethod = DB::table('payments')
                ->whereDate('payment_date', $date)
                ->where('is_refunded', false)
                ->whereNull('deleted_at')
                ->groupBy('payment_method')
                ->selectRaw('payment_method, COUNT(*) as count, COALESCE(SUM(amount), 0) as total')
                ->get()
                ->toArray();

            // Refunds on this date
            $refunds = DB::table('payments')
                ->whereDate('refunded_at', $date)
                ->where('is_refunded', true)
                ->whereNull('deleted_at')
                ->selectRaw('COUNT(*) as count')
                ->selectRaw('COALESCE(SUM(refunded_amount), 0) as total_amount')
                ->first();

            // Outstanding invoices as of this date
            $outstanding = DB::table('invoices')
                ->whereDate('invoice_date', '<=', $date)
                ->where('status', 'pending')
                ->where('balance_due', '>', 0)
                ->whereNull('deleted_at')
                ->selectRaw('COUNT(*) as count')
                ->selectRaw('COALESCE(SUM(balance_due), 0) as total_amount')
                ->first();

            return [
                'date' => $date,
                'invoices' => [
                    'count' => (int) $invoices->count,
                    'total_amount' => (float) $invoices->total_amount,
                ],
                'payments' => [
                    'count' => (int) $payments->count,
                    'total_amount' => (float) $payments->total_amount,
                ],
                'payments_by_method' => $paymentsByMethod,
                'refunds' => [
                    'count' => (int) $refunds->count,
                    'total_amount' => (float) $refunds->total_amount,
                ],
                'net_cash' => (float) $payments->total_amount - (float) $refunds->total_amount,
                'outstanding' => [
                    'count' => (int) $outstanding->count,
                    'total_amount' => (float) $outstanding->total_amount,
                ],
            ];
        } catch (Exception $e) {
            Log::error('BillingService: Error generating daily reconciliation', [
                'date' => $date,
                'error' => $e->getMessage(),
            ]);

            return [
                'date' => $date,
                'invoices' => ['count' => 0, 'total_amount' => 0.0],
                'payments' => ['count' => 0, 'total_amount' => 0.0],
                'payments_by_method' => [],
                'refunds' => ['count' => 0, 'total_amount' => 0.0],
                'net_cash' => 0.0,
                'outstanding' => ['count' => 0, 'total_amount' => 0.0],
            ];
        }
    }

    /**
     * Generate a unique invoice number.
     *
     * Format: INV-YYMMDD-XXXX
     *
     * @return string The generated invoice number
     */
    private function generateInvoiceNumber(): string
    {
        $datePrefix = now()->format('ymd');
        $prefix = "INV-{$datePrefix}-";

        $lastInvoice = Invoice::where('invoice_number', 'like', "{$prefix}%")
            ->orderBy('invoice_number', 'desc')
            ->first();

        if ($lastInvoice) {
            $lastSequence = (int) substr($lastInvoice->invoice_number, -4);
            $nextSequence = $lastSequence + 1;
        } else {
            $nextSequence = 1;
        }

        return $prefix . str_pad((string) $nextSequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Generate a unique payment number.
     *
     * Format: PAY-YYMMDD-XXXX
     *
     * @return string The generated payment number
     */
    private function generatePaymentNumber(): string
    {
        $datePrefix = now()->format('ymd');
        $prefix = "PAY-{$datePrefix}-";

        $lastPayment = Payment::where('payment_number', 'like', "{$prefix}%")
            ->orderBy('payment_number', 'desc')
            ->first();

        if ($lastPayment) {
            $lastSequence = (int) substr($lastPayment->payment_number, -4);
            $nextSequence = $lastSequence + 1;
        } else {
            $nextSequence = 1;
        }

        return $prefix . str_pad((string) $nextSequence, 4, '0', STR_PAD_LEFT);
    }
}
