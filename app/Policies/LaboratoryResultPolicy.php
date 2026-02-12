<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Clinical\LaboratoryResult;
use App\Models\User;

class LaboratoryResultPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('laboratory.view') ||
               $user->hasRole(['laboratorium', 'dokter_umum', 'dokter_spesialis', 'perawat', 'admin', 'super_admin']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, LaboratoryResult $laboratoryResult): bool
    {
        return $user->can('laboratory.view') ||
               $user->hasRole(['laboratorium', 'dokter_umum', 'dokter_spesialis', 'perawat', 'admin', 'super_admin']);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('laboratory.create') ||
               $user->hasRole(['laboratorium', 'admin', 'super_admin']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, LaboratoryResult $laboratoryResult): bool
    {
        if ($laboratoryResult->validated_at !== null) {
            return $user->hasRole(['super_admin']);
        }

        return $user->can('laboratory.edit') ||
               $user->hasRole(['laboratorium', 'admin', 'super_admin']);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, LaboratoryResult $laboratoryResult): bool
    {
        return $user->can('laboratory.delete') ||
               $user->hasRole(['admin', 'super_admin']);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, LaboratoryResult $laboratoryResult): bool
    {
        return $user->hasRole(['super_admin']);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, LaboratoryResult $laboratoryResult): bool
    {
        return $user->hasRole(['super_admin']);
    }

    /**
     * Determine whether the user can validate the laboratory result.
     */
    public function validate(User $user, LaboratoryResult $laboratoryResult): bool
    {
        return $user->hasRole(['laboratorium', 'super_admin']);
    }
}
