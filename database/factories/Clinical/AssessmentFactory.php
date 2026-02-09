<?php

declare(strict_types=1);

namespace Database\Factories\Clinical;

use App\Models\Clinical\Assessment;
use App\Models\Clinical\MedicalRecord;
use App\Models\MasterData\Employee;
use App\Models\Patient\Patient;
use App\Models\Patient\Visit;
use Illuminate\Database\Eloquent\Factories\Factory;

class AssessmentFactory extends Factory
{
    protected $model = Assessment::class;

    public function definition(): array
    {
        $weight = $this->faker->randomFloat(2, 40, 120);
        $height = $this->faker->numberBetween(140, 200);
        $systolic = $this->faker->numberBetween(90, 180);
        $diastolic = $this->faker->numberBetween(60, 110);
        $bmi = $height > 0 ? round($weight / (($height / 100) ** 2), 2) : null;

        return [
            'medical_record_id' => MedicalRecord::factory(),
            'patient_id' => Patient::factory(),
            'visit_id' => Visit::factory(),
            'assessed_by' => Employee::factory(),

            // Vital Signs (TTV)
            'systolic_bp' => $systolic,
            'diastolic_bp' => $diastolic,
            'pulse_rate' => $this->faker->randomFloat(2, 60, 100),
            'respiratory_rate' => $this->faker->randomFloat(2, 12, 20),
            'body_temperature' => $this->faker->randomFloat(2, 36, 38.5),
            'oxygen_saturation' => $this->faker->randomFloat(2, 95, 100),
            'blood_glucose' => $this->faker->optional()->randomFloat(2, 70, 140),
            'weight' => $weight,
            'height' => $height,
            'bmi' => $bmi,
            'pain_scale' => $this->faker->optional()->randomFloat(1, 0, 10),

            // Pain Assessment
            'pain_location' => $this->faker->optional()->randomElement(['kepala', 'dada', 'perut', 'punggung', 'tangan', 'kaki', 'lainnya']),
            'pain_description' => $this->faker->optional()->sentence(),

            // Consciousness
            'consciousness' => $this->faker->randomElement(['compos_mentis', 'somnolence', 'sopor', 'coma']),
            'gcs_eye' => $this->faker->optional()->numberBetween(1, 4),
            'gcs_verbal' => $this->faker->optional()->numberBetween(1, 5),
            'gcs_motor' => $this->faker->optional()->numberBetween(1, 6),
            'gcs_total' => $this->faker->optional()->numberBetween(3, 15),

            // Fall Risk Assessment
            'fall_risk' => $this->faker->optional()->randomElement(['rendah', 'sedang', 'tinggi']),
            'fall_risk_factors' => null,

            // Allergy Information
            'allergy_history' => $this->faker->optional()->paragraph(),
            'drug_allergy' => $this->faker->optional()->paragraph(),
            'food_allergy' => $this->faker->optional()->paragraph(),

            // Assessment
            'chief_complaint' => $this->faker->sentence(),
            'present_illness_history' => $this->faker->optional()->paragraph(),
            'past_medical_history' => $this->faker->optional()->paragraph(),
            'family_history' => $this->faker->optional()->paragraph(),
            'social_history' => $this->faker->optional()->paragraph(),

            // Physical Examination
            'general_condition' => $this->faker->optional()->paragraph(),
            'head_examination' => $this->faker->optional()->paragraph(),
            'neck_examination' => $this->faker->optional()->paragraph(),
            'thorax_examination' => $this->faker->optional()->paragraph(),
            'heart_examination' => $this->faker->optional()->paragraph(),
            'lung_examination' => $this->faker->optional()->paragraph(),
            'abdomen_examination' => $this->faker->optional()->paragraph(),
            'extremities_examination' => $this->faker->optional()->paragraph(),
            'neurological_examination' => $this->faker->optional()->paragraph(),
            'skin_examination' => $this->faker->optional()->paragraph(),

            // Diagnosis
            'primary_diagnosis_code' => $this->faker->optional()->regexify('[A-Z][0-9]{2}\.[0-9]'),
            'primary_diagnosis_name' => $this->faker->optional()->sentence(3),
            'secondary_diagnoses' => null,
            'diagnosis_type' => $this->faker->randomElement(['primer', 'sekunder', 'komplikasi']),

            'assessed_at' => now(),
        ];
    }

    public function withVitalSigns(array $vitalSigns): static
    {
        return $this->state(fn (array $attributes) => $vitalSigns);
    }

    public function withNormalBP(): static
    {
        return $this->state(fn (array $attributes) => [
            'systolic_bp' => 115,
            'diastolic_bp' => 75,
        ]);
    }

    public function withElevatedBP(): static
    {
        return $this->state(fn (array $attributes) => [
            'systolic_bp' => 125,
            'diastolic_bp' => 78,
        ]);
    }

    public function withStage1Hypertension(): static
    {
        return $this->state(fn (array $attributes) => [
            'systolic_bp' => 135,
            'diastolic_bp' => 85,
        ]);
    }

    public function withStage2Hypertension(): static
    {
        return $this->state(fn (array $attributes) => [
            'systolic_bp' => 150,
            'diastolic_bp' => 95,
        ]);
    }

    public function withBMI(float $weight, float $height): static
    {
        $bmi = $height > 0 ? round($weight / (($height / 100) ** 2), 2) : null;

        return $this->state(fn (array $attributes) => [
            'weight' => $weight,
            'height' => $height,
            'bmi' => $bmi,
        ]);
    }

    public function initial(): static
    {
        return $this->state(fn (array $attributes) => [
            'diagnosis_type' => 'primer',
        ]);
    }

    public function followUp(): static
    {
        return $this->state(fn (array $attributes) => [
            'diagnosis_type' => 'sekunder',
        ]);
    }
}
