<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Patient\VisitQueue;
use App\Models\User;

class VisitQueuePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('queues.view') ||
               $user->can('pendaftaran.access') ||
               $user->hasRole(['admin', 'super_admin', 'pendaftaran', 'dokter', 'perawat']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, VisitQueue $visitQueue): bool
    {
        return $user->can('queues.view') ||
               $user->can('pendaftaran.access') ||
               $user->hasRole(['admin', 'super_admin', 'pendaftaran', 'dokter', 'perawat']);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('queues.manage') ||
               $user->can('pendaftaran.access') ||
               $user->hasRole(['admin', 'super_admin', 'pendaftaran']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, VisitQueue $visitQueue): bool
    {
        return $user->can('queues.manage') ||
               $user->can('pendaftaran.access') ||
               $user->hasRole(['admin', 'super_admin', 'pendaftaran']);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, VisitQueue $visitQueue): bool
    {
        return $user->can('queues.manage') ||
               $user->hasRole(['admin', 'super_admin']);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, VisitQueue $visitQueue): bool
    {
        return $user->hasRole(['admin', 'super_admin']);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, VisitQueue $visitQueue): bool
    {
        return $user->hasRole(['super_admin']);
    }

    /**
     * Determine whether the user can call the next queue.
     */
    public function callNext(User $user): bool
    {
        return $user->can('queues.call') ||
               $user->can('pendaftaran.access') ||
               $user->hasRole(['admin', 'super_admin', 'pendaftaran']);
    }

    /**
     * Determine whether the user can call a specific queue.
     */
    public function call(User $user, VisitQueue $visitQueue): bool
    {
        return $user->can('queues.call') ||
               $user->can('pendaftaran.access') ||
               $user->hasRole(['admin', 'super_admin', 'pendaftaran']);
    }

    /**
     * Determine whether the user can skip the queue.
     */
    public function skip(User $user, VisitQueue $visitQueue): bool
    {
        return $user->can('queues.skip') ||
               $user->can('pendaftaran.access') ||
               $user->hasRole(['admin', 'super_admin', 'pendaftaran']);
    }

    /**
     * Determine whether the user can complete the queue.
     */
    public function complete(User $user, VisitQueue $visitQueue): bool
    {
        return $user->can('queues.complete') ||
               $user->can('pendaftaran.access') ||
               $user->hasRole(['admin', 'super_admin', 'pendaftaran', 'dokter', 'perawat']);
    }

    /**
     * Determine whether the user can reset the queue.
     */
    public function reset(User $user): bool
    {
        return $user->hasRole(['admin', 'super_admin']);
    }
}
