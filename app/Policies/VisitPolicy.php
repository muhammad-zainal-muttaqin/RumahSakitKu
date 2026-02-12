<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Patient\Visit;
use App\Models\User;

class VisitPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('visits.view') ||
               $user->can('pendaftaran.access') ||
               $user->hasRole(['admin', 'super_admin', 'pendaftaran', 'dokter', 'perawat', 'kasir']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Visit $visit): bool
    {
        return $user->can('visits.view') ||
               $user->can('pendaftaran.access') ||
               $user->hasRole(['admin', 'super_admin', 'pendaftaran', 'dokter', 'perawat', 'kasir']);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('visits.create') ||
               $user->can('pendaftaran.access') ||
               $user->hasRole(['admin', 'super_admin', 'pendaftaran']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Visit $visit): bool
    {
        // Cannot update completed visits
        if ($visit->is_completed) {
            return $user->hasRole(['admin', 'super_admin']);
        }

        return $user->can('visits.edit') ||
               $user->can('pendaftaran.access') ||
               $user->hasRole(['admin', 'super_admin', 'pendaftaran']);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Visit $visit): bool
    {
        // Cannot delete completed visits except by admin
        if ($visit->is_completed) {
            return $user->hasRole(['admin', 'super_admin']);
        }

        return $user->can('visits.delete') ||
               $user->hasRole(['admin', 'super_admin', 'pendaftaran']);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Visit $visit): bool
    {
        return $user->hasRole(['admin', 'super_admin']);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Visit $visit): bool
    {
        return $user->hasRole(['super_admin']);
    }

    /**
     * Determine whether the user can cancel the visit.
     */
    public function cancel(User $user, Visit $visit): bool
    {
        // Cannot cancel completed visits
        if ($visit->is_completed) {
            return false;
        }

        return $user->can('visits.cancel') ||
               $user->can('pendaftaran.access') ||
               $user->hasRole(['admin', 'super_admin', 'pendaftaran']);
    }

    /**
     * Determine whether the user can call the queue.
     */
    public function callQueue(User $user, Visit $visit): bool
    {
        return $user->can('queues.manage') ||
               $user->can('pendaftaran.access') ||
               $user->hasRole(['admin', 'super_admin', 'pendaftaran']);
    }

    /**
     * Determine whether the user can create SEP for BPJS.
     */
    public function createSep(User $user, Visit $visit): bool
    {
        return $user->can('bpjs.create_sep') ||
               $user->can('pendaftaran.access') ||
               $user->hasRole(['admin', 'super_admin', 'pendaftaran']);
    }
}
