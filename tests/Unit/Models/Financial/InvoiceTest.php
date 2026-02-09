<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Financial;

use App\Models\Financial\Invoice;
use App\Models\Financial\Payment;
use App\Models\Patient\Patient;
use App\Models\Patient\Visit;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InvoiceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_has_fillable_attributes(): void
    {
        $invoice = new Invoice();

        $expectedFillable = [
            'invoice_number',
            'visit_id',
            'patient_id',
            'invoice_type',
            'total_amount',
            'discount_amount',
            'tax_amount',
            'paid_amount',
            'remaining_amount',
            'status',
            'due_date',
            'notes',
        ];

        $this->assertEquals($expectedFillable, $invoice->getFillable());
    }

    #[Test]
    public function it_has_correct_casts(): void
    {
        $invoice = new Invoice();
        $casts = $invoice->getCasts();

        $this->assertArrayHasKey('total_amount', $casts);
        $this->assertArrayHasKey('discount_amount', $casts);
        $this->assertArrayHasKey('tax_amount', $casts);
        $this->assertArrayHasKey('paid_amount', $casts);
        $this->assertArrayHasKey('remaining_amount', $casts);
        $this->assertArrayHasKey('due_date', $casts);
        $this->assertArrayHasKey('created_at', $casts);
        $this->assertArrayHasKey('updated_at', $casts);
        $this->assertArrayHasKey('deleted_at', $casts);
    }

    #[Test]
    public function it_belongs_to_visit(): void
    {
        $invoice = new Invoice();
        $relation = $invoice->visit();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals('visit_id', $relation->getForeignKeyName());
        $this->assertInstanceOf(Visit::class, $relation->getRelated());
    }

    #[Test]
    public function it_belongs_to_patient(): void
    {
        $invoice = new Invoice();
        $relation = $invoice->patient();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals('patient_id', $relation->getForeignKeyName());
        $this->assertInstanceOf(Patient::class, $relation->getRelated());
    }

    #[Test]
    public function it_has_many_payments(): void
    {
        $invoice = new Invoice();
        $relation = $invoice->payments();

        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertEquals('invoice_id', $relation->getForeignKeyName());
        $this->assertInstanceOf(Payment::class, $relation->getRelated());
    }

    #[Test]
    public function it_can_have_multiple_payments(): void
    {
        $invoice = Invoice::factory()->create();
        Payment::factory()->count(3)->create(['invoice_id' => $invoice->id]);

        $this->assertInstanceOf(Collection::class, $invoice->payments);
        $this->assertCount(3, $invoice->payments);
        $this->assertTrue($invoice->payments->every(fn ($payment) => $payment instanceof Payment));
    }

    #[Test]
    public function it_has_with_status_scope(): void
    {
        $pendingInvoice = Invoice::factory()->pending()->create();
        Invoice::factory()->paid()->create();

        $results = Invoice::withStatus('pending')->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->contains($pendingInvoice));
    }

    #[Test]
    public function it_has_pending_scope(): void
    {
        Invoice::factory()->count(2)->pending()->create();
        Invoice::factory()->count(3)->paid()->create();

        $results = Invoice::pending()->get();

        $this->assertCount(2, $results);
        $this->assertTrue($results->every(fn ($invoice) => $invoice->status === 'pending'));
    }

    #[Test]
    public function it_has_paid_scope(): void
    {
        Invoice::factory()->count(2)->pending()->create();
        Invoice::factory()->count(3)->paid()->create();

        $results = Invoice::paid()->get();

        $this->assertCount(3, $results);
        $this->assertTrue($results->every(fn ($invoice) => $invoice->status === 'paid'));
    }

    #[Test]
    public function it_has_overdue_scope(): void
    {
        // Create overdue invoices (pending with past due date)
        Invoice::factory()->count(2)->overdue()->create();
        // Create non-overdue pending invoices (pending with future due date)
        Invoice::factory()->count(3)->pending()->create([
            'due_date' => now()->addDays(7)->format('Y-m-d'),
        ]);

        $results = Invoice::overdue()->get();

        $this->assertCount(2, $results);
        $this->assertTrue($results->every(fn ($invoice) => $invoice->status === 'pending' && $invoice->due_date < now()));
    }

    #[Test]
    public function it_has_between_dates_scope(): void
    {
        $startDate = now()->subDays(10)->format('Y-m-d');
        $endDate = now()->subDays(5)->format('Y-m-d');

        $invoiceInRange = Invoice::factory()->create(['created_at' => now()->subDays(7)]);
        Invoice::factory()->create(['created_at' => now()->subDays(15)]);
        Invoice::factory()->create(['created_at' => now()]);

        $results = Invoice::betweenDates($startDate, $endDate)->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->contains($invoiceInRange));
    }

    #[Test]
    public function it_has_with_balance_scope(): void
    {
        // Create invoices with remaining_amount > 0
        Invoice::factory()->count(2)->create([
            'total_amount' => 100000,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'paid_amount' => 0,
            'status' => 'pending',
        ]);
        // Create invoices with remaining_amount = 0 (paid)
        Invoice::factory()->count(3)->create([
            'total_amount' => 100000,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'paid_amount' => 100000,
            'status' => 'paid',
        ]);

        $results = Invoice::withBalance()->get();

        $this->assertCount(2, $results);
        $this->assertTrue($results->every(fn ($invoice) => $invoice->remaining_amount > 0));
    }

    #[Test]
    public function it_has_today_scope(): void
    {
        Invoice::factory()->today()->create();
        Invoice::factory()->create(['created_at' => now()->subDay()]);

        $results = Invoice::today()->get();

        $this->assertCount(1, $results);
    }

    #[Test]
    public function it_calculates_remaining_amount_on_saving(): void
    {
        $invoice = Invoice::factory()->create([
            'total_amount' => 100000,
            'discount_amount' => 10000,
            'tax_amount' => 9900,
            'paid_amount' => 30000,
        ]);

        // remaining = total - discount + tax - paid
        // 100000 - 10000 + 9900 - 30000 = 69900
        $this->assertEquals(69900, $invoice->remaining_amount);
    }

    #[Test]
    public function it_returns_is_paid_attribute_true_when_fully_paid(): void
    {
        $invoice = Invoice::factory()->paid()->create();

        $this->assertTrue($invoice->is_paid);
    }

    #[Test]
    public function it_returns_is_paid_attribute_false_when_not_fully_paid(): void
    {
        $invoice = Invoice::factory()->partial(50000)->create([
            'total_amount' => 100000,
        ]);

        $this->assertFalse($invoice->is_paid);
    }

    #[Test]
    public function it_returns_is_overdue_attribute_true_when_past_due(): void
    {
        $invoice = Invoice::factory()->overdue()->create();

        $this->assertTrue($invoice->is_overdue);
    }

    #[Test]
    public function it_returns_is_overdue_attribute_false_when_not_past_due(): void
    {
        $invoice = Invoice::factory()->pending()->create([
            'due_date' => now()->addDays(7)->format('Y-m-d'),
        ]);

        $this->assertFalse($invoice->is_overdue);
    }

    #[Test]
    public function it_returns_is_overdue_false_when_already_paid(): void
    {
        $invoice = Invoice::factory()->create([
            'status' => 'paid',
            'due_date' => now()->subDays(7)->format('Y-m-d'),
        ]);

        $this->assertFalse($invoice->is_overdue);
    }

    #[Test]
    public function it_calculates_payment_progress_attribute(): void
    {
        $invoice = Invoice::factory()->create([
            'total_amount' => 100000,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'paid_amount' => 25000,
        ]);

        // progress = paid / (total - discount + tax) * 100
        // 25000 / 100000 * 100 = 25%
        $this->assertEquals(25.0, $invoice->payment_progress);
    }

    #[Test]
    public function it_returns_100_payment_progress_when_total_is_zero(): void
    {
        $invoice = Invoice::factory()->create([
            'total_amount' => 0,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'paid_amount' => 0,
        ]);

        $this->assertEquals(100.0, $invoice->payment_progress);
    }

    #[Test]
    public function it_returns_formatted_total_attribute(): void
    {
        $invoice = Invoice::factory()->create([
            'total_amount' => 1500000,
        ]);

        $this->assertEquals('Rp 1.500.000', $invoice->formatted_total);
    }

    #[Test]
    public function it_uses_soft_deletes(): void
    {
        $invoice = Invoice::factory()->create();

        $this->assertDatabaseHas('invoices', ['id' => $invoice->id]);

        $invoice->delete();

        $this->assertSoftDeleted('invoices', ['id' => $invoice->id]);
    }

    #[Test]
    public function it_generates_unique_invoice_numbers(): void
    {
        $invoice1 = Invoice::factory()->create();
        $invoice2 = Invoice::factory()->create();

        $this->assertNotEquals($invoice1->invoice_number, $invoice2->invoice_number);
        $this->assertStringStartsWith('INV', $invoice1->invoice_number);
        $this->assertStringStartsWith('INV', $invoice2->invoice_number);
    }

    #[Test]
    public function it_can_create_paid_invoice(): void
    {
        $invoice = Invoice::factory()->paid()->create();

        $this->assertEquals('paid', $invoice->status);
        $this->assertEquals(0, $invoice->remaining_amount);
        $this->assertTrue($invoice->is_paid);
    }

    #[Test]
    public function it_can_create_overdue_invoice(): void
    {
        $invoice = Invoice::factory()->overdue()->create();

        $this->assertEquals('pending', $invoice->status);
        $this->assertTrue($invoice->due_date < now());
        $this->assertTrue($invoice->is_overdue);
    }
}
