<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Financial\Invoice;
use App\Models\User;

class InvoicePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('invoices.view') ||
               $user->hasRole(['kasir', 'admin', 'super_admin', 'manajemen']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Invoice $invoice): bool
    {
        return $user->can('invoices.view') ||
               $user->hasRole(['kasir', 'admin', 'super_admin', 'manajemen']);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('invoices.create') ||
               $user->hasRole(['kasir', 'admin', 'super_admin']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Invoice $invoice): bool
    {
        if ($invoice->status === 'paid') {
            return $user->hasRole(['super_admin']);
        }

        return $user->can('invoices.edit') ||
               $user->hasRole(['kasir', 'admin', 'super_admin']);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Invoice $invoice): bool
    {
        if ($invoice->status === 'paid') {
            return false;
        }

        return $user->can('invoices.delete') ||
               $user->hasRole(['admin', 'super_admin']);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Invoice $invoice): bool
    {
        return $user->hasRole(['super_admin']);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Invoice $invoice): bool
    {
        return $user->hasRole(['super_admin']);
    }

    /**
     * Determine whether the user can process payment for the invoice.
     */
    public function processPayment(User $user, Invoice $invoice): bool
    {
        return $user->hasRole(['kasir', 'super_admin']);
    }

    /**
     * Determine whether the user can process refund for the invoice.
     */
    public function processRefund(User $user, Invoice $invoice): bool
    {
        return $user->hasRole(['kasir', 'admin', 'super_admin']);
    }

    /**
     * Determine whether the user can export invoices.
     */
    public function export(User $user): bool
    {
        return $user->hasRole(['kasir', 'admin', 'super_admin', 'manajemen']);
    }
}
