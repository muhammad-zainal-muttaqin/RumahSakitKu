<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Patient\Patient;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Audit Log Model
 *
 * Records all data changes for audit trails.
 * Tracks create, update, delete operations on models.
 *
 * @property int $id
 * @property int|null $user_id
 * @property string|null $user_type
 * @property int|null $patient_id
 * @property string $auditable_type
 * @property int $auditable_id
 * @property string $event
 * @property array|null $old_values
 * @property array|null $new_values
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property string|null $url
 * @property Carbon|null $created_at
 *
 * @property-read string $event_color
 * @property-read string $event_icon
 * @property-read string $event_label
 * @property-read string $model_type_label
 * @property-read string|null $changes_summary
 * @property-read User|null $user
 * @property-read Patient\Patient|null $patient
 * @property-read Model|null $auditable
 *
 * @method static Builder|AuditLog byEvent(string $event)
 * @method static Builder|AuditLog byUser(int $userId)
 * @method static Builder|AuditLog byModelType(string $modelType)
 * @method static Builder|AuditLog dateRange(string $startDate, string $endDate)
 * @method static Builder|AuditLog byPatient(int $patientId)
 */
class AuditLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'user_type',
        'patient_id',
        'auditable_type',
        'auditable_id',
        'event',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'url',
        'created_at',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Get the user who performed the action.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the patient associated with this audit log.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Get the auditable model.
     */
    public function auditable()
    {
        return $this->morphTo();
    }

    /**
     * Get event color for badge.
     */
    public function getEventColorAttribute(): string
    {
        return match ($this->event) {
            'created' => 'success',
            'updated' => 'warning',
            'deleted' => 'danger',
            'restored' => 'info',
            'force_deleted' => 'gray',
            default => 'gray',
        };
    }

    /**
     * Get event icon.
     */
    public function getEventIconAttribute(): string
    {
        return match ($this->event) {
            'created' => 'heroicon-o-plus-circle',
            'updated' => 'heroicon-o-pencil-square',
            'deleted' => 'heroicon-o-trash',
            'restored' => 'heroicon-o-arrow-uturn-left',
            'force_deleted' => 'heroicon-o-x-circle',
            default => 'heroicon-o-question-mark-circle',
        };
    }

    /**
     * Get event label in Indonesian.
     */
    public function getEventLabelAttribute(): string
    {
        return match ($this->event) {
            'created' => 'Dibuat',
            'updated' => 'Diperbarui',
            'deleted' => 'Dihapus',
            'restored' => 'Dipulihkan',
            'force_deleted' => 'Dihapus Permanen',
            default => $this->event,
        };
    }

    /**
     * Get the model type label.
     */
    public function getModelTypeLabelAttribute(): string
    {
        $className = class_basename($this->auditable_type);
        
        return match ($className) {
            'Patient' => 'Pasien',
            'Visit' => 'Kunjungan',
            'MedicalRecord' => 'Rekam Medis',
            'Prescription' => 'Resep',
            'Assessment' => 'Asesmen',
            'Cppt' => 'CPPT',
            'Employee' => 'Pegawai',
            'User' => 'Pengguna',
            'Medicine' => 'Obat',
            'Room' => 'Kamar',
            'Bed' => 'Tempat Tidur',
            'Invoice' => 'Tagihan',
            'Payment' => 'Pembayaran',
            'Polyclinic' => 'Poliklinik',
            'LaboratoryOrder' => 'Order Laboratorium',
            'RadiologyOrder' => 'Order Radiologi',
            default => $className,
        };
    }

    /**
     * Get changes summary.
     */
    public function getChangesSummaryAttribute(): ?string
    {
        if ($this->event === 'created') {
            return 'Record baru dibuat';
        }

        if ($this->event === 'deleted') {
            return 'Record dihapus';
        }

        if ($this->event === 'restored') {
            return 'Record dipulihkan';
        }

        if ($this->old_values && $this->new_values) {
            $changedFields = array_keys($this->new_values);
            $count = count($changedFields);
            
            if ($count === 1) {
                return "Field '{$changedFields[0]}' diubah";
            }
            
            return "{$count} field diubah: " . implode(', ', array_slice($changedFields, 0, 3)) . ($count > 3 ? '...' : '');
        }

        return null;
    }

    /**
     * Scope a query to filter by event.
     */
    public function scopeByEvent($query, string $event)
    {
        return $query->where('event', $event);
    }

    /**
     * Scope a query to filter by user.
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to filter by auditable type.
     */
    public function scopeByModelType($query, string $modelType)
    {
        return $query->where('auditable_type', $modelType);
    }

    /**
     * Scope a query to filter by date range.
     */
    public function scopeDateRange($query, string $startDate, string $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope a query to filter by patient.
     */
    public function scopeByPatient($query, int $patientId)
    {
        return $query->where('patient_id', $patientId);
    }
}
