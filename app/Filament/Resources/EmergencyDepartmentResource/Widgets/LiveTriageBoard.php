<?php

declare(strict_types=1);

namespace App\Filament\Resources\EmergencyDepartmentResource\Widgets;

use App\Models\Patient\Visit;
use App\Services\TriageService;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class LiveTriageBoard extends Widget
{
    protected string $view = 'filament.resources.emergency-department-resource.widgets.live-triage-board';

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 2;

    /**
     * Polling interval in seconds
     */
    public ?string $pollingInterval = '5s';

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return [
            'redPatients' => $this->getPatientsByTriage(TriageService::CATEGORY_RED),
            'yellowPatients' => $this->getPatientsByTriage(TriageService::CATEGORY_YELLOW),
            'greenPatients' => $this->getPatientsByTriage(TriageService::CATEGORY_GREEN),
            'inProgressPatients' => $this->getInProgressPatients(),
        ];
    }

    /**
     * Get patients by triage category
     *
     * @param string $category
     * @return Collection<int, Visit>
     */
    private function getPatientsByTriage(string $category): Collection
    {
        return Visit::query()
            ->where('visit_type', 'igd')
            ->whereDate('visit_date', today())
            ->whereIn('status', ['registered', 'waiting'])
            ->whereHas('medicalRecord.assessments', function ($query) use ($category) {
                $query->where('assessment_type', 'triage')
                    ->where('triage_category', $category);
            })
            ->with(['patient', 'medicalRecord.assessments' => function ($query) use ($category) {
                $query->where('assessment_type', 'triage')
                    ->where('triage_category', $category);
            }])
            ->orderBy('check_in_at', 'asc')
            ->get()
            ->map(function (Visit $visit) {
                $visit->wait_time = $this->calculateWaitTime($visit);
                return $visit;
            });
    }

    /**
     * Get patients currently being treated
     *
     * @return Collection<int, Visit>
     */
    private function getInProgressPatients(): Collection
    {
        return Visit::query()
            ->where('visit_type', 'igd')
            ->whereDate('visit_date', today())
            ->where('status', 'in_progress')
            ->with(['patient', 'doctor', 'medicalRecord.assessments' => function ($query) {
                $query->where('assessment_type', 'triage');
            }])
            ->orderBy('check_in_at', 'asc')
            ->get()
            ->map(function (Visit $visit) {
                $visit->treatment_duration = $this->calculateTreatmentDuration($visit);
                $assessment = $visit->medicalRecord?->assessments->first();
                $visit->triage_category = $assessment?->triage_category ?? 'unknown';
                return $visit;
            });
    }

    /**
     * Calculate wait time for a visit
     */
    private function calculateWaitTime(Visit $visit): string
    {
        $startTime = $visit->check_in_at ?? $visit->created_at;
        $diff = $startTime->diffInMinutes(now());

        if ($diff < 60) {
            return $diff . ' menit';
        }

        $hours = floor($diff / 60);
        $minutes = $diff % 60;

        return $hours . 'j ' . $minutes . 'm';
    }

    /**
     * Calculate treatment duration for a visit
     */
    private function calculateTreatmentDuration(Visit $visit): string
    {
        $diff = $visit->check_in_at?->diffInMinutes(now()) ?? 0;

        if ($diff < 60) {
            return $diff . ' menit';
        }

        $hours = floor($diff / 60);
        $minutes = $diff % 60;

        return $hours . 'j ' . $minutes . 'm';
    }

    /**
     * Get triage category color class
     */
    public function getTriageColorClass(string $category): string
    {
        return match ($category) {
            TriageService::CATEGORY_RED => 'bg-danger-50 border-danger-200 text-danger-700',
            TriageService::CATEGORY_YELLOW => 'bg-warning-50 border-warning-200 text-warning-700',
            TriageService::CATEGORY_GREEN => 'bg-success-50 border-success-200 text-success-700',
            TriageService::CATEGORY_BLACK => 'bg-gray-50 border-gray-200 text-gray-700',
            default => 'bg-gray-50 border-gray-200 text-gray-700',
        };
    }

    /**
     * Get triage badge color
     */
    public function getTriageBadgeColor(string $category): string
    {
        return TriageService::getCategoryColor($category);
    }

    /**
     * Get triage label
     */
    public function getTriageLabel(string $category): string
    {
        return TriageService::getCategoryShortLabel($category);
    }
}
