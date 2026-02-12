<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Clinical\MedicalRecord;
use App\Models\User;

class MedicalRecordPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('medical_records.view') ||
               $user->hasRole(['dokter_umum', 'dokter_spesialis', 'perawat', 'admin', 'super_admin']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, MedicalRecord $medicalRecord): bool
    {
        return $user->can('medical_records.view') ||
               $user->hasRole(['dokter_umum', 'dokter_spesialis', 'perawat', 'admin', 'super_admin']);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('medical_records.create') ||
               $user->hasRole(['dokter_umum', 'dokter_spesialis', 'perawat', 'admin', 'super_admin']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, MedicalRecord $medicalRecord): bool
    {
        if ($medicalRecord->is_finalized) {
            return $user->hasRole(['super_admin']);
        }

        return $user->can('medical_records.edit') ||
               $user->hasRole(['dokter_umum', 'dokter_spesialis', 'admin', 'super_admin']);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('medical_records.delete') ||
               $user->hasRole(['admin', 'super_admin']);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ?MedicalRecord $medicalRecord = null): bool
    {
        return $this->deleteAny($user);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, MedicalRecord $medicalRecord): bool
    {
        return $user->hasRole(['super_admin']);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, MedicalRecord $medicalRecord): bool
    {
        return $user->hasRole(['super_admin']);
    }

    /**
     * Determine whether the user can finalize the medical record.
     */
    public function finalize(User $user, MedicalRecord $medicalRecord): bool
    {
        return $user->hasRole(['dokter_umum', 'dokter_spesialis', 'super_admin']);
    }

    /**
     * Determine whether the user can add CPPT to the medical record.
     */
    public function addCppt(User $user, MedicalRecord $medicalRecord): bool
    {
        return $user->hasRole(['dokter_umum', 'dokter_spesialis', 'perawat', 'super_admin']);
    }
}
