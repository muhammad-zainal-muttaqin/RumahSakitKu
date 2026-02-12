<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Patient\Patient;
use App\Models\User;

class PatientPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('patients.view') ||
               $user->can('pendaftaran.access') ||
               $user->hasRole(['admin', 'super_admin', 'pendaftaran', 'dokter', 'perawat']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Patient $patient): bool
    {
        return $user->can('patients.view') ||
               $user->can('pendaftaran.access') ||
               $user->hasRole(['admin', 'super_admin', 'pendaftaran', 'dokter', 'perawat']);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('patients.create') ||
               $user->can('pendaftaran.access') ||
               $user->hasRole(['admin', 'super_admin', 'pendaftaran']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Patient $patient): bool
    {
        return $user->can('patients.edit') ||
               $user->can('pendaftaran.access') ||
               $user->hasRole(['admin', 'super_admin', 'pendaftaran']);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Patient $patient): bool
    {
        return $user->can('patients.delete') ||
               $user->hasRole(['admin', 'super_admin']);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Patient $patient): bool
    {
        return $user->hasRole(['admin', 'super_admin']);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Patient $patient): bool
    {
        return $user->hasRole(['super_admin']);
    }

    /**
     * Determine whether the user can export patients.
     */
    public function export(User $user): bool
    {
        return $user->can('patients.export') ||
               $user->hasRole(['admin', 'super_admin', 'pendaftaran']);
    }

    /**
     * Determine whether the user can print patient card.
     */
    public function printCard(User $user, Patient $patient): bool
    {
        return $user->can('patients.print_card') ||
               $user->can('pendaftaran.access') ||
               $user->hasRole(['admin', 'super_admin', 'pendaftaran']);
    }

    /**
     * Determine whether the user can create visit from patient.
     */
    public function createVisit(User $user, Patient $patient): bool
    {
        return $user->can('visits.create') ||
               $user->can('pendaftaran.access') ||
               $user->hasRole(['admin', 'super_admin', 'pendaftaran']);
    }
}
