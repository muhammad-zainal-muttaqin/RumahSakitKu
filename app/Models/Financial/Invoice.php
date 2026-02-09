<?php

declare(strict_types=1);

namespace App\Models\Financial;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Concerns\HasAuditLogs;
use App\Models\Patient\Patient;
use App\Models\Patient\Visit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Invoice Model
 *
 * Represents a billing invoice for a patient visit.
 * Manages charges, payments, and payment status.
 *
 * @property int $id
 * @property string $invoice_number
 * @property int $visit_id
 * @property int $patient_id
 * @property string $invoice_type
 * @property float $total_amount
 * @property float $discount_amount
 * @property float $tax_amount
 * @property float $paid_amount
 * @property float $remaining_amount
 * @property string $status
 * @property Carbon|null $due_date
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 *
 * @property-read bool $is_paid
 * @property-read bool $is_overdue
 * @property-read float $payment_progress
 * @property-read string $formatted_total
 * @property-read Visit $visit
 * @property-read Patient $patient
 * @property-read Collection|Payment[] $payments
 *
 * @method static Builder|Invoice withStatus(string $status)
 * @method static Builder|Invoice pending()
 * @method static Builder|Invoice paid()
 * @method static Builder|Invoice overdue()
 * @method static Builder|Invoice betweenDates($startDate, $endDate)
 * @method static Builder|Invoice withBalance()
 * @method static Builder|Invoice today()
 */
class Invoice extends Model
{
    use HasFactory;
    use SoftDeletes;
    use HasAuditLogs;

    protected $table = 'invoices';

    protected $fillable = [
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

    protected $casts = [
        'total_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'total' => 'decimal:2',
        'balance_due' => 'decimal:2',
        'due_date' => 'date',
        'invoice_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Normalize legacy aliases used across older flows/tests.
     *
     * @param array<string, mixed> $attributes
     * @return array<string, mixed>
     */
    private function normalizeLegacyAttributes(array $attributes): array
    {
        if (array_key_exists('invoice_type', $attributes) && $attributes['invoice_type'] !== null) {
            $rawType = strtolower((string) $attributes['invoice_type']);
            $attributes['invoice_type'] = match ($rawType) {
                'outpatient', 'rawat-jalan', 'rawat_jalan' => 'rawat_jalan',
                'inpatient', 'rawat-inap', 'rawat_inap' => 'rawat_inap',
                default => $attributes['invoice_type'],
            };
        }

        if (array_key_exists('subtotal', $attributes) && !array_key_exists('total_amount', $attributes)) {
            $attributes['total_amount'] = $attributes['subtotal'];
        }

        if (array_key_exists('total', $attributes) && !array_key_exists('total_amount', $attributes)) {
            $attributes['total_amount'] = $attributes['total'];
        }

        if (array_key_exists('balance_due', $attributes) && !array_key_exists('remaining_amount', $attributes)) {
            $attributes['remaining_amount'] = $attributes['balance_due'];
        }

        if (array_key_exists('payment_status', $attributes) && !array_key_exists('status', $attributes)) {
            $attributes['status'] = match ($attributes['payment_status']) {
                'paid' => 'paid',
                'partial' => 'partial',
                'cancelled' => 'cancelled',
                default => 'pending',
            };
        }

        if (array_key_exists('status', $attributes) && $attributes['status'] !== null) {
            $rawStatus = strtolower((string) $attributes['status']);
            $attributes['status'] = match ($rawStatus) {
                'unpaid' => 'pending',
                default => $attributes['status'],
            };
        }

        unset($attributes['subtotal'], $attributes['total'], $attributes['balance_due'], $attributes['payment_status']);

        return $attributes;
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function fill(array $attributes): static
    {
        $legacy = [];
        $legacyKeys = [
            'invoice_date',
            'subtotal',
            'total',
            'balance_due',
            'payment_status',
            'insurance_claim_amount',
            'insurance_claim_status',
            'created_at',
        ];

        foreach ($legacyKeys as $key) {
            if (array_key_exists($key, $attributes)) {
                $legacy[$key] = $attributes[$key];
                unset($attributes[$key]);
            }
        }

        $normalizedInput = $attributes;
        foreach (['subtotal', 'total', 'balance_due', 'payment_status'] as $key) {
            if (array_key_exists($key, $legacy) && !array_key_exists($key, $normalizedInput)) {
                $normalizedInput[$key] = $legacy[$key];
            }
        }

        $model = parent::fill($this->normalizeLegacyAttributes($normalizedInput));

        if (array_key_exists('created_at', $legacy)) {
            $this->setAttribute('created_at', $legacy['created_at']);
        } elseif (array_key_exists('invoice_date', $legacy)) {
            $this->setAttribute('created_at', $legacy['invoice_date']);
        }

        foreach (['invoice_date', 'subtotal', 'total', 'balance_due', 'payment_status', 'insurance_claim_amount', 'insurance_claim_status'] as $key) {
            if (array_key_exists($key, $legacy)) {
                $this->setAttribute($key, $legacy[$key]);
            }
        }

        return $model;
    }

    /**
     * Get the visit associated with this invoice.
     */
    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class, 'visit_id');
    }

    /**
     * Get the patient that owns this invoice.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    /**
     * Get all payments for this invoice.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'invoice_id');
    }

    /**
     * Scope a query to filter by invoice status.
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include pending invoices.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include paid invoices.
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    /**
     * Scope a query to only include overdue invoices.
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', 'pending')
            ->where('due_date', '<', now());
    }

    /**
     * Scope a query to filter by invoice date range.
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope a query to only include invoices with remaining balance.
     */
    public function scopeWithBalance($query)
    {
        return $query->where('remaining_amount', '>', 0);
    }

    /**
     * Scope a query to only include invoices for today.
     */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    /**
     * Calculate remaining amount before saving.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::saving(function ($invoice) {
            $invoice->discount_amount = (float) ($invoice->discount_amount ?? 0);
            $invoice->tax_amount = (float) ($invoice->tax_amount ?? 0);
            $invoice->paid_amount = (float) ($invoice->paid_amount ?? 0);
            $invoice->total_amount = (float) ($invoice->total_amount ?? 0);

            if (empty($invoice->invoice_type)) {
                $visitType = null;
                if (!empty($invoice->visit_id)) {
                    $visit = $invoice->relationLoaded('visit')
                        ? $invoice->getRelation('visit')
                        : $invoice->visit()->first();
                    $visitType = $visit?->visit_type;
                }

                $invoice->invoice_type = in_array($visitType, ['rawat_inap', 'inpatient'], true)
                    ? 'rawat_inap'
                    : 'rawat_jalan';
            }

            if (empty($invoice->status)) {
                $invoice->status = 'pending';
            }

            $invoice->remaining_amount = $invoice->total_amount - $invoice->discount_amount + $invoice->tax_amount - $invoice->paid_amount;
            $invoice->subtotal = $invoice->total_amount;
            $invoice->total = $invoice->total_amount;
            $invoice->balance_due = $invoice->remaining_amount;
            $invoice->invoice_date = $invoice->invoice_date ?? $invoice->created_at ?? now();

            if (empty($invoice->payment_status)) {
                $invoice->payment_status = match (true) {
                    $invoice->status === 'cancelled' => 'cancelled',
                    (float) $invoice->remaining_amount <= 0 => 'paid',
                    (float) $invoice->paid_amount > 0 => 'partial',
                    default => 'unpaid',
                };
            }
        });
    }

    public function getInvoiceDateAttribute($value): ?Carbon
    {
        if ($value) {
            return Carbon::parse((string) $value);
        }

        return $this->created_at;
    }

    public function getSubtotalAttribute($value): float
    {
        return (float) ($value ?? $this->total_amount ?? 0);
    }

    public function getBalanceDueAttribute($value): float
    {
        return (float) ($value ?? $this->remaining_amount ?? 0);
    }

    public function getPaymentStatusAttribute($value): string
    {
        if ($value !== null && $value !== '') {
            return (string) $value;
        }

        return match (true) {
            $this->status === 'cancelled' => 'cancelled',
            (float) $this->remaining_amount <= 0 => 'paid',
            (float) $this->paid_amount > 0 => 'partial',
            default => 'unpaid',
        };
    }

    /**
     * Check if invoice is fully paid.
     */
    public function getIsPaidAttribute(): bool
    {
        return $this->remaining_amount <= 0;
    }

    /**
     * Check if invoice is overdue.
     */
    public function getIsOverdueAttribute(): bool
    {
        return $this->status === 'pending' && $this->due_date && $this->due_date->isPast();
    }

    /**
     * Get the payment progress percentage.
     */
    public function getPaymentProgressAttribute(): float
    {
        $total = $this->total_amount - $this->discount_amount + $this->tax_amount;
        if ($total <= 0) {
            return 100.0;
        }

        return round(($this->paid_amount / $total) * 100, 2);
    }

    /**
     * Get formatted invoice amount.
     */
    public function getFormattedTotalAttribute(): string
    {
        return 'Rp ' . number_format((float) $this->total_amount, 0, ',', '.');
    }
}
