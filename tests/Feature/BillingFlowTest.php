<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Financial\Invoice;
use App\Models\Financial\Payment;
use App\Models\MasterData\Employee;
use App\Models\MasterData\Polyclinic;
use App\Models\MasterData\Procedure;
use App\Models\Patient\Patient;
use App\Models\Patient\Visit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BillingFlowTest extends TestCase
{
    use RefreshDatabase;

    protected User $cashierUser;
    protected User $adminUser;
    protected Employee $cashier;
    protected Patient $patient;
    protected Visit $visit;
    protected Polyclinic $polyclinic;
    protected Invoice $invoice;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::create(['name' => 'admin', 'guard_name' => 'web']);
        Role::create(['name' => 'cashier', 'guard_name' => 'web']);
        Role::create(['name' => 'registration', 'guard_name' => 'web']);

        // Create polyclinic
        $this->polyclinic = Polyclinic::factory()->create([
            'name' => 'Poli Umum',
            'is_active' => true,
        ]);

        // Create cashier
        $this->cashier = Employee::factory()->create([
            'employee_type' => 'cashier',
            'status' => 'aktif',
        ]);

        // Create users
        $this->cashierUser = User::factory()->create([
            'is_active' => true,
            'employee_id' => $this->cashier->id,
        ]);
        $this->cashierUser->assignRole('cashier');

        $this->adminUser = User::factory()->create(['is_active' => true]);
        $this->adminUser->assignRole('admin');

        // Create patient and visit
        $this->patient = Patient::factory()->create();
        $this->visit = Visit::factory()->create([
            'patient_id' => $this->patient->id,
            'polyclinic_id' => $this->polyclinic->id,
            'visit_date' => now(),
            'is_completed' => true,
        ]);

        // Create invoice
        $this->invoice = Invoice::factory()->create([
            'invoice_number' => 'INV' . now()->format('Ymd') . '001',
            'visit_id' => $this->visit->id,
            'patient_id' => $this->patient->id,
            'invoice_date' => now(),
            'due_date' => now()->addDays(7),
            'subtotal' => 500000,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total_amount' => 500000,
            'paid_amount' => 0,
            'balance_due' => 500000,
            'status' => 'pending',
            'payment_status' => 'unpaid',
        ]);
    }

    /**
     * Test cashier can view pending invoices.
     */
    public function test_cashier_can_view_pending_invoices(): void
    {
        $response = $this->actingAs($this->cashierUser)
            ->get('/admin/billing/invoices');

        $response->assertStatus(200);
        $response->assertSee($this->invoice->invoice_number);
    }

    /**
     * Test cashier can view invoice details.
     */
    public function test_cashier_can_view_invoice_details(): void
    {
        $response = $this->actingAs($this->cashierUser)
            ->get("/admin/billing/invoices/{$this->invoice->id}");

        $response->assertStatus(200);
        $response->assertSee($this->invoice->invoice_number);
        $response->assertSee('500.000');
    }

    /**
     * Test invoice is generated from visit.
     */
    public function test_invoice_is_generated_from_visit(): void
    {
        $visit = Visit::factory()->create([
            'patient_id' => $this->patient->id,
            'polyclinic_id' => $this->polyclinic->id,
            'is_completed' => true,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post("/admin/visits/{$visit->id}/generate-invoice");

        $response->assertRedirect();

        $this->assertDatabaseHas('invoices', [
            'visit_id' => $visit->id,
            'patient_id' => $this->patient->id,
        ]);
    }

    /**
     * Test cashier can add cash payment.
     */
    public function test_cashier_can_add_cash_payment(): void
    {
        $paymentData = [
            'invoice_id' => $this->invoice->id,
            'payment_date' => now()->format('Y-m-d'),
            'amount' => 500000,
            'payment_method' => 'cash',
            'payment_type' => 'full_payment',
            'received_by' => $this->cashier->id,
            'notes' => 'Pembayaran tunai',
        ];

        $response = $this->actingAs($this->cashierUser)
            ->post('/admin/billing/payments', $paymentData);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('payments', [
            'invoice_id' => $this->invoice->id,
            'amount' => 500000,
            'payment_method' => 'cash',
        ]);
    }

    /**
     * Test cashier can add partial payment.
     */
    public function test_cashier_can_add_partial_payment(): void
    {
        $paymentData = [
            'invoice_id' => $this->invoice->id,
            'payment_date' => now()->format('Y-m-d'),
            'amount' => 200000,
            'payment_method' => 'cash',
            'payment_type' => 'partial_payment',
            'received_by' => $this->cashier->id,
        ];

        $response = $this->actingAs($this->cashierUser)
            ->post('/admin/billing/payments', $paymentData);

        $response->assertRedirect();

        $this->invoice->refresh();
        $this->assertEquals(200000, $this->invoice->paid_amount);
        $this->assertEquals(300000, $this->invoice->balance_due);
        $this->assertEquals('partial', $this->invoice->payment_status);
    }

    /**
     * Test cashier can add card payment.
     */
    public function test_cashier_can_add_card_payment(): void
    {
        $paymentData = [
            'invoice_id' => $this->invoice->id,
            'payment_date' => now()->format('Y-m-d'),
            'amount' => 500000,
            'payment_method' => 'credit_card',
            'payment_type' => 'full_payment',
            'card_number' => '4111111111111111',
            'card_type' => 'visa',
            'approval_code' => '123456',
            'received_by' => $this->cashier->id,
        ];

        $response = $this->actingAs($this->cashierUser)
            ->post('/admin/billing/payments', $paymentData);

        $response->assertRedirect();

        $this->assertDatabaseHas('payments', [
            'invoice_id' => $this->invoice->id,
            'payment_method' => 'credit_card',
            'card_type' => 'visa',
        ]);
    }

    /**
     * Test cashier can add bank transfer payment.
     */
    public function test_cashier_can_add_bank_transfer_payment(): void
    {
        $paymentData = [
            'invoice_id' => $this->invoice->id,
            'payment_date' => now()->format('Y-m-d'),
            'amount' => 500000,
            'payment_method' => 'bank_transfer',
            'payment_type' => 'full_payment',
            'bank_name' => 'BCA',
            'account_number' => '1234567890',
            'account_holder' => 'Rumah Sakit Ku',
            'reference_number' => 'TRF123456',
            'received_by' => $this->cashier->id,
        ];

        $response = $this->actingAs($this->cashierUser)
            ->post('/admin/billing/payments', $paymentData);

        $response->assertRedirect();

        $this->assertDatabaseHas('payments', [
            'invoice_id' => $this->invoice->id,
            'payment_method' => 'bank_transfer',
            'bank_name' => 'BCA',
        ]);
    }

    /**
     * Test invoice balance is calculated correctly after payment.
     */
    public function test_invoice_balance_is_calculated_correctly_after_payment(): void
    {
        Payment::create([
            'payment_number' => 'PAY' . now()->format('Ymd') . '001',
            'invoice_id' => $this->invoice->id,
            'payment_date' => now(),
            'amount' => 500000,
            'payment_method' => 'cash',
            'payment_type' => 'full_payment',
            'received_by' => $this->cashier->id,
        ]);

        $this->invoice->refresh();

        $this->assertEquals(500000, $this->invoice->paid_amount);
        $this->assertEquals(0, $this->invoice->balance_due);
        $this->assertTrue($this->invoice->is_paid);
        $this->assertEquals('paid', $this->invoice->payment_status);
        $this->assertEquals('paid', $this->invoice->status);
    }

    /**
     * Test cannot pay more than invoice total.
     */
    public function test_cannot_pay_more_than_invoice_total(): void
    {
        $response = $this->actingAs($this->cashierUser)
            ->post('/admin/billing/payments', [
                'invoice_id' => $this->invoice->id,
                'payment_date' => now()->format('Y-m-d'),
                'amount' => 600000, // More than total
                'payment_method' => 'cash',
                'payment_type' => 'full_payment',
            ]);

        $response->assertSessionHasErrors('amount');
    }

    /**
     * Test payment requires valid payment method.
     */
    public function test_payment_requires_valid_payment_method(): void
    {
        $response = $this->actingAs($this->cashierUser)
            ->post('/admin/billing/payments', [
                'invoice_id' => $this->invoice->id,
                'payment_date' => now()->format('Y-m-d'),
                'amount' => 500000,
                'payment_method' => 'invalid_method',
                'payment_type' => 'full_payment',
            ]);

        $response->assertSessionHasErrors('payment_method');
    }

    /**
     * Test cashier can print receipt.
     */
    public function test_cashier_can_print_receipt(): void
    {
        $payment = Payment::create([
            'payment_number' => 'PAY' . now()->format('Ymd') . '001',
            'invoice_id' => $this->invoice->id,
            'payment_date' => now(),
            'amount' => 500000,
            'payment_method' => 'cash',
            'payment_type' => 'full_payment',
            'received_by' => $this->cashier->id,
        ]);

        $response = $this->actingAs($this->cashierUser)
            ->get("/admin/billing/payments/{$payment->id}/receipt");

        $response->assertStatus(200);
        $response->assertSee('Kwitansi');
        $response->assertSee($payment->payment_number);
    }

    /**
     * Test cashier can print invoice.
     */
    public function test_cashier_can_print_invoice(): void
    {
        $response = $this->actingAs($this->cashierUser)
            ->get("/admin/billing/invoices/{$this->invoice->id}/print");

        $response->assertStatus(200);
        $response->assertSee('Invoice');
        $response->assertSee($this->invoice->invoice_number);
    }

    /**
     * Test invoice shows payment progress.
     */
    public function test_invoice_shows_payment_progress(): void
    {
        Payment::create([
            'payment_number' => 'PAY' . now()->format('Ymd') . '001',
            'invoice_id' => $this->invoice->id,
            'payment_date' => now(),
            'amount' => 250000,
            'payment_method' => 'cash',
            'payment_type' => 'partial_payment',
            'received_by' => $this->cashier->id,
        ]);

        $this->invoice->refresh();

        $this->assertEquals(50.00, $this->invoice->payment_progress);
    }

    /**
     * Test invoice can have multiple payments.
     */
    public function test_invoice_can_have_multiple_payments(): void
    {
        Payment::create([
            'payment_number' => 'PAY' . now()->format('Ymd') . '001',
            'invoice_id' => $this->invoice->id,
            'payment_date' => now(),
            'amount' => 200000,
            'payment_method' => 'cash',
            'payment_type' => 'partial_payment',
            'received_by' => $this->cashier->id,
        ]);

        Payment::create([
            'payment_number' => 'PAY' . now()->format('Ymd') . '002',
            'invoice_id' => $this->invoice->id,
            'payment_date' => now(),
            'amount' => 300000,
            'payment_method' => 'credit_card',
            'payment_type' => 'partial_payment',
            'received_by' => $this->cashier->id,
        ]);

        $this->invoice->refresh();

        $this->assertEquals(2, $this->invoice->payments()->count());
        $this->assertEquals(500000, $this->invoice->paid_amount);
        $this->assertTrue($this->invoice->is_paid);
    }

    /**
     * Test BPJS invoice is marked as insurance payment.
     */
    public function test_bpjs_invoice_is_marked_as_insurance_payment(): void
    {
        $bpjsPatient = Patient::factory()->create([
            'insurance_type' => 'bpjs',
            'bpjs_card_number' => '0001234567890',
        ]);

        $visit = Visit::factory()->create([
            'patient_id' => $bpjsPatient->id,
            'polyclinic_id' => $this->polyclinic->id,
            'bpjs_sep_number' => '0123R0010124V000001',
        ]);

        $invoice = Invoice::factory()->create([
            'visit_id' => $visit->id,
            'patient_id' => $bpjsPatient->id,
            'total_amount' => 1000000,
            'insurance_claim_amount' => 1000000,
            'insurance_claim_status' => 'pending',
        ]);

        $this->assertEquals('pending', $invoice->insurance_claim_status);
    }

    /**
     * Test invoice can be cancelled.
     */
    public function test_invoice_can_be_cancelled(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post("/admin/billing/invoices/{$this->invoice->id}/cancel", [
                'cancellation_reason' => 'Kesalahan input',
            ]);

        $response->assertRedirect();

        $this->invoice->refresh();
        $this->assertEquals('cancelled', $this->invoice->status);
    }

    /**
     * Test paid invoice cannot be cancelled.
     */
    public function test_paid_invoice_cannot_be_cancelled(): void
    {
        $this->invoice->update([
            'paid_amount' => 500000,
            'balance_due' => 0,
            'status' => 'paid',
            'payment_status' => 'paid',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post("/admin/billing/invoices/{$this->invoice->id}/cancel", [
                'cancellation_reason' => 'Kesalahan input',
            ]);

        $response->assertStatus(403);
    }

    /**
     * Test payment can be refunded.
     */
    public function test_payment_can_be_refunded(): void
    {
        $payment = Payment::create([
            'payment_number' => 'PAY' . now()->format('Ymd') . '001',
            'invoice_id' => $this->invoice->id,
            'payment_date' => now(),
            'amount' => 500000,
            'payment_method' => 'cash',
            'payment_type' => 'full_payment',
            'received_by' => $this->cashier->id,
        ]);

        $this->invoice->refresh();

        $response = $this->actingAs($this->adminUser)
            ->post("/admin/billing/payments/{$payment->id}/refund", [
                'refund_amount' => 500000,
                'refund_reason' => 'Pembatalan layanan',
            ]);

        $response->assertRedirect();

        $payment->refresh();
        $this->assertTrue($payment->is_refunded);
        $this->assertEquals(500000, $payment->refunded_amount);
        $this->assertNotNull($payment->refunded_at);
    }

    /**
     * Test cannot refund more than payment amount.
     */
    public function test_cannot_refund_more_than_payment_amount(): void
    {
        $payment = Payment::create([
            'payment_number' => 'PAY' . now()->format('Ymd') . '001',
            'invoice_id' => $this->invoice->id,
            'payment_date' => now(),
            'amount' => 500000,
            'payment_method' => 'cash',
            'payment_type' => 'full_payment',
            'received_by' => $this->cashier->id,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post("/admin/billing/payments/{$payment->id}/refund", [
                'refund_amount' => 600000,
                'refund_reason' => 'Pembatalan layanan',
            ]);

        $response->assertSessionHasErrors('refund_amount');
    }

    /**
     * Test invoice with discount calculates correctly.
     */
    public function test_invoice_with_discount_calculates_correctly(): void
    {
        $this->invoice->update([
            'subtotal' => 500000,
            'discount_amount' => 50000,
            'total_amount' => 450000,
        ]);

        $this->assertEquals(450000, $this->invoice->total_amount);

        Payment::create([
            'payment_number' => 'PAY' . now()->format('Ymd') . '001',
            'invoice_id' => $this->invoice->id,
            'payment_date' => now(),
            'amount' => 450000,
            'payment_method' => 'cash',
            'received_by' => $this->cashier->id,
        ]);

        $this->invoice->refresh();
        $this->assertTrue($this->invoice->is_paid);
    }

    /**
     * Test invoice search by patient name.
     */
    public function test_can_search_invoice_by_patient_name(): void
    {
        $response = $this->actingAs($this->cashierUser)
            ->get('/admin/billing/invoices?search=' . $this->patient->name);

        $response->assertStatus(200);
        $response->assertSee($this->invoice->invoice_number);
    }

    /**
     * Test invoice filter by status.
     */
    public function test_can_filter_invoice_by_status(): void
    {
        $response = $this->actingAs($this->cashierUser)
            ->get('/admin/billing/invoices?status=pending');

        $response->assertStatus(200);
        $response->assertSee($this->invoice->invoice_number);
    }

    /**
     * Test invoice filter by date range.
     */
    public function test_can_filter_invoice_by_date_range(): void
    {
        $response = $this->actingAs($this->cashierUser)
            ->get('/admin/billing/invoices?start_date=' . now()->format('Y-m-d') . '&end_date=' . now()->format('Y-m-d'));

        $response->assertStatus(200);
        $response->assertSee($this->invoice->invoice_number);
    }

    /**
     * Test overdue invoices are flagged.
     */
    public function test_overdue_invoices_are_flagged(): void
    {
        $overdueInvoice = Invoice::factory()->create([
            'visit_id' => $this->visit->id,
            'patient_id' => $this->patient->id,
            'invoice_date' => now()->subDays(10),
            'due_date' => now()->subDays(3),
            'status' => 'pending',
            'total_amount' => 500000,
            'paid_amount' => 0,
        ]);

        $this->assertTrue($overdueInvoice->is_overdue);
    }

    /**
     * Test today's payments report.
     */
    public function test_can_view_todays_payments_report(): void
    {
        Payment::create([
            'payment_number' => 'PAY' . now()->format('Ymd') . '001',
            'invoice_id' => $this->invoice->id,
            'payment_date' => now(),
            'amount' => 500000,
            'payment_method' => 'cash',
            'received_by' => $this->cashier->id,
        ]);

        $response = $this->actingAs($this->cashierUser)
            ->get('/admin/billing/reports/today');

        $response->assertStatus(200);
        $response->assertSee('500.000');
    }

    /**
     * Test complete billing workflow.
     */
    public function test_complete_billing_workflow(): void
    {
        // 1. View invoice
        $viewResponse = $this->actingAs($this->cashierUser)
            ->get("/admin/billing/invoices/{$this->invoice->id}");
        $viewResponse->assertStatus(200);

        // 2. Add payment
        $paymentResponse = $this->actingAs($this->cashierUser)
            ->post('/admin/billing/payments', [
                'invoice_id' => $this->invoice->id,
                'payment_date' => now()->format('Y-m-d'),
                'amount' => 500000,
                'payment_method' => 'cash',
                'payment_type' => 'full_payment',
                'received_by' => $this->cashier->id,
            ]);
        $paymentResponse->assertRedirect();

        // 3. Verify invoice is paid
        $this->invoice->refresh();
        $this->assertTrue($this->invoice->is_paid);
        $this->assertEquals('paid', $this->invoice->status);

        // 4. Print receipt
        $payment = Payment::where('invoice_id', $this->invoice->id)->first();
        $receiptResponse = $this->actingAs($this->cashierUser)
            ->get("/admin/billing/payments/{$payment->id}/receipt");
        $receiptResponse->assertStatus(200);

        // 5. Verify payment exists
        $this->assertDatabaseHas('payments', [
            'invoice_id' => $this->invoice->id,
            'amount' => 500000,
            'payment_method' => 'cash',
        ]);
    }

    /**
     * Test non-cashier cannot access billing routes.
     */
    public function test_non_cashier_cannot_access_billing_routes(): void
    {
        $registrationUser = User::factory()->create(['is_active' => true]);
        $registrationUser->assignRole('registration');

        $response = $this->actingAs($registrationUser)
            ->get('/admin/billing/invoices');

        $response->assertStatus(403);
    }

    /**
     * Test invoice number format.
     */
    public function test_invoice_number_format(): void
    {
        $this->assertMatchesRegularExpression('/^INV\d{8}\d{3}$/', $this->invoice->invoice_number);
    }

    /**
     * Test payment number format.
     */
    public function test_payment_number_format(): void
    {
        $payment = Payment::create([
            'payment_number' => 'PAY' . now()->format('Ymd') . '001',
            'invoice_id' => $this->invoice->id,
            'payment_date' => now(),
            'amount' => 500000,
            'payment_method' => 'cash',
            'received_by' => $this->cashier->id,
        ]);

        $this->assertMatchesRegularExpression('/^PAY\d{8}\d{3}$/', $payment->payment_number);
    }
}
