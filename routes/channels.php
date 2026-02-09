<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

/**
 * Queue updates for specific polyclinic
 * Used for real-time queue display updates
 */
Broadcast::channel('polyclinic.{polyclinicId}', function ($user, int $polyclinicId): bool {
    return $user->can('view_queues') || $user->can('manage_queues');
});

/**
 * Emergency alerts channel (admin and doctors only)
 * Used for critical emergency notifications
 */
Broadcast::channel('emergency', function ($user): bool {
    return $user->hasRole('admin') 
        || $user->hasRole('dokter') 
        || $user->hasRole('perawat')
        || $user->can('view_emergency_alerts');
});

/**
 * User private notifications channel
 * Each user can only listen to their own private channel
 */
Broadcast::channel('App.Models.User.{id}', function ($user, int $id): bool {
    return (int) $user->id === (int) $id;
});

/**
 * Surgery updates channel (surgery team)
 * Real-time updates for surgery status changes
 */
Broadcast::channel('surgery.{surgeryId}', function ($user, int $surgeryId): bool {
    return $user->can('view_surgeries') 
        || $user->can('manage_surgeries')
        || $user->can('perform_surgeries');
});

/**
 * Inpatient updates channel
 * Bed occupancy and inpatient status updates
 */
Broadcast::channel('inpatient', function ($user): bool {
    return $user->can('view_inpatients') 
        || $user->can('manage_inpatients')
        || $user->hasRole('admin');
});

/**
 * Room occupancy updates channel
 * Real-time bed availability updates
 */
Broadcast::channel('rooms.occupancy', function ($user): bool {
    return $user->can('view_rooms') 
        || $user->can('manage_rooms')
        || $user->can('view_inpatients');
});

/**
 * Laboratory results channel
 * Real-time lab results notifications
 */
Broadcast::channel('laboratory.{orderId}', function ($user, int $orderId): bool {
    return $user->can('view_laboratory_orders') 
        || $user->can('manage_laboratory')
        || $user->can('view_patient_results');
});

/**
 * Radiology results channel
 * Real-time radiology results notifications
 */
Broadcast::channel('radiology.{orderId}', function ($user, int $orderId): bool {
    return $user->can('view_radiology_orders') 
        || $user->can('manage_radiology')
        || $user->can('view_patient_results');
});

/**
 * Pharmacy prescription channel
 * Prescription status updates
 */
Broadcast::channel('pharmacy.{prescriptionId}', function ($user, int $prescriptionId): bool {
    return $user->can('view_prescriptions') 
        || $user->can('manage_pharmacy')
        || $user->can('dispense_medicines');
});

/**
 * Triage updates channel (IGD)
 * Emergency department triage updates
 */
Broadcast::channel('triage', function ($user): bool {
    return $user->can('view_triage') 
        || $user->hasRole('dokter')
        || $user->hasRole('perawat')
        || $user->hasRole('admin');
});
