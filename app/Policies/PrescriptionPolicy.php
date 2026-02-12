<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Clinical\Prescription;
use App\Models\User;

class PrescriptionPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('prescriptions.view') ||
               $user->hasRole(['dokter_umum', 'dokter_spesialis', 'farmasi', 'perawat', 'admin', 'super_admin']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Prescription $prescription): bool
    {
        return $user->can('prescriptions.view') ||
               $user->hasRole(['dokter_umum', 'dokter_spesialis', 'farmasi', 'perawat', 'admin', 'super_admin']);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('prescriptions.create') ||
               $user->hasRole(['dokter_umum', 'dokter_spesialis', 'admin', 'super_admin']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Prescription $prescription): bool
    {
        if (in_array($prescription->status, ['completed', 'cancelled'])) {
            return $user->hasRole(['super_admin']);
        }

        return $user->can('prescriptions.edit') ||
               $user->hasRole(['dokter_umum', 'dokter_spesialis', 'farmasi', 'admin', 'super_admin']);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Prescription $prescription): bool
    {
        if ($prescription->status === 'completed') {
            return false;
        }

        return $user->can('prescriptions.delete') ||
               $user->hasRole(['admin', 'super_admin']);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Prescription $prescription): bool
    {
        return $user->hasRole(['super_admin']);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Prescription $prescription): bool
    {
        return $user->hasRole(['super_admin']);
    }

    /**
     * Determine whether the user can verify the prescription.
     */
    public function verify(User $user, Prescription $prescription): bool
    {
        return $user->hasRole(['farmasi', 'super_admin']);
    }

    /**
     * Determine whether the user can dispense the prescription.
     */
    public function dispense(User $user, Prescription $prescription): bool
    {
        return $user->hasRole(['farmasi', 'super_admin']);
    }
}
