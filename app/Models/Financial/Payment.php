<?php

declare(strict_types=1);

namespace App\Models\Financial;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Concerns\HasAuditLogs;
use App\Services\CacheService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

/**
 * Payment Model
 *
 * Represents a payment transaction for an invoice.
 * Handles multiple payment methods and refund tracking.
 *
 * @property int $id
 * @property string $payment_number
 * @property int $invoice_id
 * @property Carbon|null $payment_date
 * @property Carbon|null $payment_time
 * @property float $amount
 * @property string $payment_method
 * @property string|null $payment_type
 * @property string|null $reference_number
 * @property string|null $bank_name
 * @property string|null $account_number
 * @property string|null $account_holder
 * @property string|null $card_number
 * @property string|null $card_type
 * @property string|null $approval_code
 * @property string|null $received_by
 * @property string|null $notes
 * @property bool $is_refunded
 * @property float|null $refunded_amount
 * @property Carbon|null $refunded_at
 * @property string|null $refund_reason
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property int|null $created_by
 * @property int|null $updated_by
 *
 * @property-read string $formatted_amount
 * @property-read string $payment_method_label
 * @property-read bool $can_be_refunded
 * @property-read float $refundable_amount
 * @property-read Invoice $invoice
 *
 * @method static Builder|Payment byMethod(string $method)
 * @method static Builder|Payment onDate($date)
 * @method static Builder|Payment betweenDates($startDate, $endDate)
 * @method static Builder|Payment today()
 * @method static Builder|Payment refunded()
 * @method static Builder|Payment notRefunded()
 * @method static Builder|Payment cash()
 * @method static Builder|Payment card()
 * @method static Builder|Payment transfer()
 */
class Payment extends Model
{
    use HasFactory;
    use SoftDeletes;
    use HasAuditLogs;

    protected $table = 'payments';

    protected $fillable = [
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

    protected $casts = [
        'payment_date' => 'date',
        'payment_time' => 'datetime',
        'amount' => 'decimal:2',
        'refunded_amount' => 'decimal:2',
        'refunded_at' => 'datetime',
        'is_refunded' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $payment): void {
            if (empty($payment->payment_number)) {
                $payment->payment_number = 'PAY' . strtoupper(substr(uniqid('', true), -10));
            }

            if (empty($payment->payment_date)) {
                $payment->payment_date = now()->toDateString();
            }

            if (empty($payment->payment_method)) {
                $payment->payment_method = 'cash';
            }

            if (empty($payment->invoice_id)) {
                $payment->invoice_id = Invoice::factory()->create()->id;
            }
        });

        static::created(function (self $payment): void {
            $invoice = $payment->invoice;
            if (!$invoice) {
                return;
            }

            $newPaidAmount = (float) $invoice->paid_amount + (float) $payment->amount;
            $newBalance = (float) $invoice->total_amount - $newPaidAmount;

            $invoice->update([
                'paid_amount' => $newPaidAmount,
                'remaining_amount' => max(0, $newBalance),
                'balance_due' => max(0, $newBalance),
                'payment_status' => $newBalance <= 0 ? 'paid' : 'partial',
                'status' => $newBalance <= 0 ? 'paid' : 'pending',
            ]);
        });

        static::saved(function (self $payment): void {
            self::invalidateRevenueMetricsCache($payment);
        });

        static::deleted(function (self $payment): void {
            self::invalidateRevenueMetricsCache($payment);
        });
    }

    private static function invalidateRevenueMetricsCache(self $payment): void
    {
        $now = now();
        $dailyTrendEnd = $now->copy()->endOfDay()->format('Y-m-d');

        foreach ([1, 7, 30] as $days) {
            $dailyTrendStart = $now->copy()->subDays($days - 1)->startOfDay()->format('Y-m-d');
            Cache::forget("trend:revenue:daily:{$dailyTrendStart}:{$dailyTrendEnd}");
        }

        $year = $payment->payment_date instanceof Carbon
            ? $payment->payment_date->year
            : ($payment->payment_date ? Carbon::parse((string) $payment->payment_date)->year : $now->year);

        Cache::forget("trend:revenue:monthly:{$now->year}");
        Cache::forget("trend:revenue:monthly:{$year}");

        Cache::forget("stats_overview_today_{$now->copy()->startOfDay()->format('Ymd')}");
        Cache::forget("stats_overview_week_{$now->copy()->startOfWeek()->format('Ymd')}");
        Cache::forget("stats_overview_month_{$now->copy()->startOfMonth()->format('Ymd')}");
        Cache::forget("stats_overview_year_{$now->copy()->startOfYear()->format('Ymd')}");

        CacheService::flushPattern('trend:revenue:daily:*');
        CacheService::flushPattern('trend:revenue:monthly:*');
        CacheService::flushPattern('stats_overview_*');
    }

    /**
     * Get the invoice associated with this payment.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    /**
     * Scope a query to filter by payment method.
     */
    public function scopeByMethod($query, string $method)
    {
        return $query->where('payment_method', $method);
    }

    /**
     * Scope a query to filter by payment date.
     */
    public function scopeOnDate($query, $date)
    {
        return $query->whereDate('payment_date', $date);
    }

    /**
     * Scope a query to filter by date range.
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('payment_date', [$startDate, $endDate]);
    }

    /**
     * Scope a query to only include payments for today.
     */
    public function scopeToday($query)
    {
        return $query->whereDate('payment_date', today());
    }

    /**
     * Scope a query to only include refunded payments.
     */
    public function scopeRefunded($query)
    {
        return $query->where('is_refunded', true);
    }

    /**
     * Scope a query to only include non-refunded payments.
     */
    public function scopeNotRefunded($query)
    {
        return $query->where('is_refunded', false);
    }

    /**
     * Scope a query to only include cash payments.
     */
    public function scopeCash($query)
    {
        return $query->where('payment_method', 'cash');
    }

    /**
     * Scope a query to only include card payments.
     */
    public function scopeCard($query)
    {
        return $query->whereIn('payment_method', ['credit_card', 'debit_card']);
    }

    /**
     * Scope a query to only include transfer payments.
     */
    public function scopeTransfer($query)
    {
        return $query->where('payment_method', 'bank_transfer');
    }

    /**
     * Get the formatted payment amount.
     */
    public function getFormattedAmountAttribute(): string
    {
        return 'Rp ' . number_format((float) $this->amount, 0, ',', '.');
    }

    /**
     * Get the payment method label.
     */
    public function getPaymentMethodLabelAttribute(): string
    {
        $labels = [
            'cash' => 'Cash',
            'credit_card' => 'Credit Card',
            'debit_card' => 'Debit Card',
            'bank_transfer' => 'Bank Transfer',
            'mobile_payment' => 'Mobile Payment',
            'insurance' => 'Insurance',
            'bpjs' => 'BPJS',
        ];

        return $labels[$this->payment_method] ?? ucfirst(str_replace('_', ' ', $this->payment_method));
    }

    /**
     * Check if payment can be refunded.
     */
    public function getCanBeRefundedAttribute(): bool
    {
        return !$this->is_refunded && $this->amount > 0;
    }

    /**
     * Get the remaining refundable amount.
     */
    public function getRefundableAmountAttribute(): float
    {
        $amount = (float) $this->amount;
        $refunded = (float) ($this->refunded_amount ?? 0);

        return $amount - $refunded;
    }

    /**
     * Process a refund for this payment.
     */
    public function refund(float $amount, string $reason): bool
    {
        if (!$this->can_be_refunded || $amount > $this->refundable_amount) {
            return false;
        }

        $this->refunded_amount = ($this->refunded_amount ?? 0) + $amount;
        $this->refund_reason = $reason;
        $this->refunded_at = now();
        $this->is_refunded = $this->refunded_amount >= $this->amount;

        return $this->save();
    }
}
