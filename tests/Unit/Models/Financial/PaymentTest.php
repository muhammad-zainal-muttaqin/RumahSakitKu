<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Financial;

use App\Models\Financial\Invoice;
use App\Models\Financial\Payment;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_has_fillable_attributes(): void
    {
        $payment = new Payment();

        $expectedFillable = [
            'payment_number',
            'invoice_id',
            'payment_date',
            'payment_time',
            'amount',
            'payment_method',
            'payment_type',
            'reference_number',
            'bank_name',
            'account_number',
            'account_holder',
            'card_number',
            'card_type',
            'approval_code',
            'received_by',
            'notes',
            'is_refunded',
            'refunded_amount',
            'refunded_at',
            'refund_reason',
            'created_by',
            'updated_by',
        ];

        $this->assertEquals($expectedFillable, $payment->getFillable());
    }

    #[Test]
    public function it_has_correct_casts(): void
    {
        $payment = new Payment();
        $casts = $payment->getCasts();

        $this->assertArrayHasKey('payment_date', $casts);
        $this->assertArrayHasKey('payment_time', $casts);
        $this->assertArrayHasKey('amount', $casts);
        $this->assertArrayHasKey('refunded_amount', $casts);
        $this->assertArrayHasKey('refunded_at', $casts);
        $this->assertArrayHasKey('is_refunded', $casts);
        $this->assertArrayHasKey('created_at', $casts);
        $this->assertArrayHasKey('updated_at', $casts);
        $this->assertArrayHasKey('deleted_at', $casts);
    }

    #[Test]
    public function it_belongs_to_invoice(): void
    {
        $payment = new Payment();
        $relation = $payment->invoice();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals('invoice_id', $relation->getForeignKeyName());
        $this->assertInstanceOf(Invoice::class, $relation->getRelated());
    }

    #[Test]
    public function it_has_by_method_scope(): void
    {
        $cashPayment = Payment::factory()->cash()->create();
        Payment::factory()->creditCard()->create();

        $results = Payment::byMethod('cash')->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->contains($cashPayment));
    }

    #[Test]
    public function it_has_on_date_scope(): void
    {
        $today = now()->format('Y-m-d');
        $yesterday = now()->subDay()->format('Y-m-d');

        $todayPayment = Payment::factory()->create(['payment_date' => $today]);
        Payment::factory()->create(['payment_date' => $yesterday]);

        $results = Payment::onDate($today)->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->contains($todayPayment));
    }

    #[Test]
    public function it_has_between_dates_scope(): void
    {
        $startDate = now()->subDays(10)->format('Y-m-d');
        $endDate = now()->subDays(5)->format('Y-m-d');

        $paymentInRange = Payment::factory()->create(['payment_date' => now()->subDays(7)->format('Y-m-d')]);
        Payment::factory()->create(['payment_date' => now()->subDays(15)->format('Y-m-d')]);
        Payment::factory()->create(['payment_date' => now()->format('Y-m-d')]);

        $results = Payment::betweenDates($startDate, $endDate)->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->contains($paymentInRange));
    }

    #[Test]
    public function it_has_today_scope(): void
    {
        Payment::factory()->today()->create();
        Payment::factory()->create(['payment_date' => now()->subDay()->format('Y-m-d')]);

        $results = Payment::today()->get();

        $this->assertCount(1, $results);
    }

    #[Test]
    public function it_has_refunded_scope(): void
    {
        Payment::factory()->count(2)->refunded(50000)->create();
        Payment::factory()->count(3)->create();

        $results = Payment::refunded()->get();

        $this->assertCount(2, $results);
        $this->assertTrue($results->every(fn ($payment) => $payment->is_refunded === true));
    }

    #[Test]
    public function it_has_not_refunded_scope(): void
    {
        Payment::factory()->count(2)->refunded(50000)->create();
        Payment::factory()->count(3)->create();

        $results = Payment::notRefunded()->get();

        $this->assertCount(3, $results);
        $this->assertTrue($results->every(fn ($payment) => $payment->is_refunded === false));
    }

    #[Test]
    public function it_has_cash_scope(): void
    {
        Payment::factory()->count(2)->cash()->create();
        Payment::factory()->count(3)->creditCard()->create();

        $results = Payment::cash()->get();

        $this->assertCount(2, $results);
        $this->assertTrue($results->every(fn ($payment) => $payment->payment_method === 'cash'));
    }

    #[Test]
    public function it_has_card_scope(): void
    {
        Payment::factory()->count(2)->cash()->create();
        Payment::factory()->count(3)->creditCard()->create();

        $results = Payment::card()->get();

        $this->assertCount(3, $results);
        $this->assertTrue($results->every(fn ($payment) => in_array($payment->payment_method, ['credit_card', 'debit_card'])));
    }

    #[Test]
    public function it_has_transfer_scope(): void
    {
        Payment::factory()->count(2)->cash()->create();
        Payment::factory()->count(3)->bankTransfer()->create();

        $results = Payment::transfer()->get();

        $this->assertCount(3, $results);
        $this->assertTrue($results->every(fn ($payment) => $payment->payment_method === 'bank_transfer'));
    }

    #[Test]
    public function it_returns_formatted_amount_attribute(): void
    {
        $payment = Payment::factory()->create([
            'amount' => 1500000,
        ]);

        $this->assertEquals('Rp 1.500.000', $payment->formatted_amount);
    }

    #[Test]
    public function it_returns_payment_method_label_attribute(): void
    {
        $cashPayment = Payment::factory()->cash()->create();
        $creditCardPayment = Payment::factory()->creditCard()->create();
        $bankTransferPayment = Payment::factory()->bankTransfer()->create();

        $this->assertEquals('Cash', $cashPayment->payment_method_label);
        $this->assertEquals('Credit Card', $creditCardPayment->payment_method_label);
        $this->assertEquals('Bank Transfer', $bankTransferPayment->payment_method_label);
    }

    #[Test]
    public function it_returns_can_be_refunded_attribute_true_when_not_refunded(): void
    {
        $payment = Payment::factory()->create([
            'amount' => 100000,
            'is_refunded' => false,
        ]);

        $this->assertTrue($payment->can_be_refunded);
    }

    #[Test]
    public function it_returns_can_be_refunded_attribute_false_when_already_refunded(): void
    {
        $payment = Payment::factory()->refunded(100000)->create([
            'amount' => 100000,
        ]);

        $this->assertFalse($payment->can_be_refunded);
    }

    #[Test]
    public function it_returns_refundable_amount_attribute_when_not_refunded(): void
    {
        $payment = Payment::factory()->create([
            'amount' => 100000,
            'is_refunded' => false,
        ]);

        $this->assertEquals(100000, $payment->refundable_amount);
    }

    #[Test]
    public function it_returns_refundable_amount_attribute_when_partially_refunded(): void
    {
        $payment = Payment::factory()->partialRefund(30000)->create([
            'amount' => 100000,
        ]);

        $this->assertEquals(70000, $payment->refundable_amount);
    }

    #[Test]
    public function it_can_process_full_refund(): void
    {
        $payment = Payment::factory()->create([
            'amount' => 100000,
            'is_refunded' => false,
        ]);

        $result = $payment->refund(100000, 'Customer request');

        $this->assertTrue($result);
        $this->assertTrue($payment->fresh()->is_refunded);
        $this->assertEquals(100000, $payment->fresh()->refunded_amount);
        $this->assertNotNull($payment->fresh()->refunded_at);
        $this->assertEquals('Customer request', $payment->fresh()->refund_reason);
    }

    #[Test]
    public function it_can_process_partial_refund(): void
    {
        $payment = Payment::factory()->create([
            'amount' => 100000,
            'is_refunded' => false,
        ]);

        $result = $payment->refund(30000, 'Partial refund');

        $this->assertTrue($result);
        $this->assertFalse($payment->fresh()->is_refunded);
        $this->assertEquals(30000, $payment->fresh()->refunded_amount);
        $this->assertNotNull($payment->fresh()->refunded_at);
    }

    #[Test]
    public function it_cannot_refund_more_than_amount(): void
    {
        $payment = Payment::factory()->create([
            'amount' => 100000,
            'is_refunded' => false,
        ]);

        $result = $payment->refund(150000, 'Over refund');

        $this->assertFalse($result);
        $this->assertFalse($payment->fresh()->is_refunded);
        $this->assertNull($payment->fresh()->refunded_amount);
    }

    #[Test]
    public function it_cannot_refund_already_fully_refunded_payment(): void
    {
        $payment = Payment::factory()->refunded(100000)->create([
            'amount' => 100000,
        ]);

        $result = $payment->refund(50000, 'Additional refund');

        $this->assertFalse($result);
    }

    #[Test]
    public function it_uses_soft_deletes(): void
    {
        $payment = Payment::factory()->create();

        $this->assertDatabaseHas('payments', ['id' => $payment->id]);

        $payment->delete();

        $this->assertSoftDeleted('payments', ['id' => $payment->id]);
    }

    #[Test]
    public function it_generates_unique_payment_numbers(): void
    {
        $payment1 = Payment::factory()->create();
        $payment2 = Payment::factory()->create();

        $this->assertNotEquals($payment1->payment_number, $payment2->payment_number);
        $this->assertStringStartsWith('PAY', $payment1->payment_number);
        $this->assertStringStartsWith('PAY', $payment2->payment_number);
    }

    #[Test]
    public function it_can_create_cash_payment(): void
    {
        $payment = Payment::factory()->cash()->create();

        $this->assertEquals('cash', $payment->payment_method);
        $this->assertNull($payment->bank_name);
        $this->assertNull($payment->card_number);
    }

    #[Test]
    public function it_can_create_credit_card_payment(): void
    {
        $payment = Payment::factory()->creditCard()->create();

        $this->assertEquals('credit_card', $payment->payment_method);
        $this->assertNotNull($payment->bank_name);
        $this->assertNotNull($payment->card_number);
        $this->assertNotNull($payment->card_type);
        $this->assertNotNull($payment->approval_code);
    }

    #[Test]
    public function it_can_create_bank_transfer_payment(): void
    {
        $payment = Payment::factory()->bankTransfer()->create();

        $this->assertEquals('bank_transfer', $payment->payment_method);
        $this->assertNotNull($payment->bank_name);
        $this->assertNotNull($payment->account_number);
        $this->assertNotNull($payment->account_holder);
    }

    #[Test]
    public function it_can_create_refunded_payment(): void
    {
        $payment = Payment::factory()->refunded(50000, 'Customer request')->create([
            'amount' => 100000,
        ]);

        $this->assertTrue($payment->is_refunded);
        $this->assertEquals(50000, $payment->refunded_amount);
        $this->assertNotNull($payment->refunded_at);
        $this->assertEquals('Customer request', $payment->refund_reason);
    }

    #[Test]
    public function it_can_create_partially_refunded_payment(): void
    {
        $payment = Payment::factory()->partialRefund(30000)->create([
            'amount' => 100000,
        ]);

        $this->assertFalse($payment->is_refunded);
        $this->assertEquals(30000, $payment->refunded_amount);
        $this->assertNotNull($payment->refunded_at);
    }
}
