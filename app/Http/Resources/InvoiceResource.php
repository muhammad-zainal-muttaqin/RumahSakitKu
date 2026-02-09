<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property int $id
 * @property string $invoice_number
 * @property int $patient_id
 * @property int|null $visit_id
 * @property string|null $invoice_date
 * @property float $subtotal
 * @property float $discount
 * @property float $tax
 * @property float $total
 * @property float $paid_amount
 * @property string|null $status
 * @property string|null $payment_status
 */
class InvoiceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_number' => $this->invoice_number,
            'patient' => $this->whenLoaded('patient', fn() => [
                'id' => $this->patient->id,
                'name' => $this->patient->name,
                'medical_record_number' => $this->patient->medical_record_number,
            ]),
            'visit' => $this->whenLoaded('visit', fn() => [
                'id' => $this->visit->id,
                'visit_number' => $this->visit->visit_number,
            ]),
            'invoice_date' => $this->invoice_date,
            'subtotal' => (float) $this->subtotal,
            'discount' => (float) $this->discount,
            'tax' => (float) $this->tax,
            'total' => (float) $this->total,
            'paid_amount' => (float) $this->paid_amount,
            'balance' => (float) ($this->total - $this->paid_amount),
            'status' => $this->status,
            'payment_status' => $this->payment_status,
            'payment_status_label' => $this->getPaymentStatusLabel(),
            'notes' => $this->notes,
            'items' => $this->whenLoaded('items', fn() => $this->items->map(fn($item) => [
                'id' => $item->id,
                'item_type' => $item->item_type,
                'item_name' => $item->item_name,
                'quantity' => $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'discount' => (float) ($item->discount ?? 0),
                'total' => (float) $item->total,
            ])),
            'payments_count' => $this->whenCounted('payments'),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

    /**
     * Get payment status label.
     *
     * @return string
     */
    private function getPaymentStatusLabel(): string
    {
        return match ($this->payment_status) {
            'unpaid' => 'Belum Dibayar',
            'partial' => 'Dibayar Sebagian',
            'paid' => 'Lunas',
            'refunded' => 'Dikembalikan',
            default => $this->payment_status,
        };
    }
}
