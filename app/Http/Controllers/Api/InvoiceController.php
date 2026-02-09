<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use Exception;
use App\Http\Resources\InvoiceResource;
use App\Models\Financial\Invoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Billing & Invoice API Controller.
 * 
 * Handles invoice creation, payment processing, and payment history.
 */
class InvoiceController extends BaseController
{
    /**
     * Display a listing of invoices.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = Invoice::query()
            ->with(['patient', 'visit', 'items'])
            ->when($request->search, function ($q, $search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('patient', function ($sub) use ($search) {
                        $sub->where('name', 'like', "%{$search}%")
                            ->orWhere('medical_record_number', 'like', "%{$search}%");
                    });
            })
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->when($request->payment_status, fn($q, $p) => $q->where('payment_status', $p))
            ->when($request->patient_id, fn($q, $p) => $q->where('patient_id', $p))
            ->when($request->visit_id, fn($q, $v) => $q->where('visit_id', $v))
            ->when($request->from_date, fn($q, $d) => $q->whereDate('invoice_date', '>=', $d))
            ->when($request->to_date, fn($q, $d) => $q->whereDate('invoice_date', '<=', $d));

        $invoices = $query->latest('invoice_date')->paginate($request->per_page ?? 20);

        return $this->paginateResponse($invoices);
    }

    /**
     * Store a newly created invoice.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'visit_id' => ['required', 'exists:visits,id'],
            'patient_id' => ['required', 'exists:patients,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_type' => ['required', 'in:registration,consultation,procedure,medicine,lab,radiology,room,other'],
            'items.*.item_id' => ['nullable', 'integer'],
            'items.*.item_name' => ['required', 'string'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.discount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        try {
            DB::beginTransaction();

            $subtotal = 0;
            $discount = 0;

            foreach ($validated['items'] as $item) {
                $itemTotal = $item['quantity'] * $item['unit_price'];
                $itemDiscount = $item['discount'] ?? 0;
                $subtotal += $itemTotal;
                $discount += $itemDiscount;
            }

            $invoice = Invoice::create([
                'visit_id' => $validated['visit_id'],
                'patient_id' => $validated['patient_id'],
                'invoice_number' => $this->generateInvoiceNumber(),
                'invoice_date' => now(),
                'subtotal' => $subtotal,
                'discount' => $discount,
                'tax' => 0, // Calculate based on tax rules
                'total' => $subtotal - $discount,
                'status' => 'active',
                'payment_status' => 'unpaid',
                'notes' => $validated['notes'] ?? null,
                'created_by' => $request->user()->id,
            ]);

            foreach ($validated['items'] as $item) {
                $item['total'] = ($item['quantity'] * $item['unit_price']) - ($item['discount'] ?? 0);
                $invoice->items()->create($item);
            }

            DB::commit();

            return $this->createdResponse(
                new InvoiceResource($invoice->load(['patient', 'visit', 'items'])),
                'Invoice created successfully'
            );
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Failed to create invoice: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified invoice.
     *
     * @param Invoice $invoice
     * @return JsonResponse
     */
    public function show(Invoice $invoice): JsonResponse
    {
        return $this->successResponse(
            new InvoiceResource($invoice->load([
                'patient',
                'visit',
                'items',
                'payments',
            ]))
        );
    }

    /**
     * Process payment for invoice.
     *
     * @param Request $request
     * @param Invoice $invoice
     * @return JsonResponse
     */
    public function pay(Request $request, Invoice $invoice): JsonResponse
    {
        if ($invoice->payment_status === 'paid') {
            return $this->errorResponse('Invoice is already fully paid', 422);
        }

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'payment_method' => ['required', 'in:cash,debit,credit,transfer,qris,bpjs,insurance'],
            'payment_reference' => ['nullable', 'string'],
            'bpjs_number' => ['nullable', 'string', 'required_if:payment_method,bpjs'],
            'notes' => ['nullable', 'string'],
        ]);

        $remainingAmount = $invoice->total - $invoice->paid_amount;

        if ($validated['amount'] > $remainingAmount) {
            return $this->errorResponse('Payment amount exceeds remaining balance', 422);
        }

        try {
            DB::beginTransaction();

            $payment = $invoice->payments()->create([
                'payment_number' => $this->generatePaymentNumber(),
                'amount' => $validated['amount'],
                'payment_method' => $validated['payment_method'],
                'payment_reference' => $validated['payment_reference'] ?? null,
                'bpjs_number' => $validated['bpjs_number'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'payment_date' => now(),
                'received_by' => $request->user()->id,
            ]);

            $newPaidAmount = $invoice->paid_amount + $validated['amount'];
            $paymentStatus = $newPaidAmount >= $invoice->total ? 'paid' : 'partial';

            $invoice->update([
                'paid_amount' => $newPaidAmount,
                'payment_status' => $paymentStatus,
            ]);

            DB::commit();

            return $this->successResponse([
                'payment' => $payment,
                'invoice' => new InvoiceResource($invoice->fresh()->load(['patient', 'payments'])),
                'remaining_balance' => $invoice->total - $newPaidAmount,
            ], 'Payment processed successfully');
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Failed to process payment: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get payment history for invoice.
     *
     * @param Request $request
     * @param Invoice $invoice
     * @return JsonResponse
     */
    public function payments(Request $request, Invoice $invoice): JsonResponse
    {
        $payments = $invoice->payments()
            ->with('receivedBy')
            ->latest()
            ->paginate($request->per_page ?? 15);

        return $this->paginateResponse($payments);
    }

    /**
     * Void an invoice.
     *
     * @param Request $request
     * @param Invoice $invoice
     * @return JsonResponse
     */
    public function void(Request $request, Invoice $invoice): JsonResponse
    {
        if ($invoice->status === 'voided') {
            return $this->errorResponse('Invoice is already voided', 422);
        }

        $validated = $request->validate([
            'void_reason' => ['required', 'string'],
        ]);

        try {
            DB::beginTransaction();

            $invoice->update([
                'status' => 'voided',
                'voided_by' => $request->user()->id,
                'voided_at' => now(),
                'void_reason' => $validated['void_reason'],
            ]);

            DB::commit();

            return $this->successResponse(
                new InvoiceResource($invoice->fresh()),
                'Invoice voided successfully'
            );
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Failed to void invoice: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Generate unique invoice number.
     *
     * @return string
     */
    private function generateInvoiceNumber(): string
    {
        $prefix = 'INV';
        $date = date('Ymd');
        $lastInvoice = Invoice::whereDate('created_at', today())
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastInvoice ? ((int) substr($lastInvoice->invoice_number, -4) + 1) : 1;

        return sprintf('%s%s%04d', $prefix, $date, $sequence);
    }

    /**
     * Generate unique payment number.
     *
     * @return string
     */
    private function generatePaymentNumber(): string
    {
        $prefix = 'PAY';
        $date = date('Ymd');
        $lastPayment = DB::table('payments')
            ->whereDate('created_at', today())
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastPayment ? ((int) substr($lastPayment->payment_number, -4) + 1) : 1;

        return sprintf('%s%s%04d', $prefix, $date, $sequence);
    }
}
