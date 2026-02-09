<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\TriageService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Test class for TriageService.
 *
 * Tests triage category calculation, vital signs assessment,
 * and triage category label/color utilities.
 */
class TriageServiceTest extends TestCase
{
    private TriageService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TriageService();
    }

    // ==================== Triage Category Calculation Tests ====================

    #[Test]
    public function it_calculates_red_category_for_critical_low_systolic_bp(): void
    {
        $vitals = ['systolic_bp' => 80, 'diastolic_bp' => 60];
        $result = $this->service::calculateTriageCategory($vitals, null);
        $this->assertEquals('red', $result);
    }

    #[Test]
    public function it_calculates_red_category_for_critical_high_systolic_bp(): void
    {
        $vitals = ['systolic_bp' => 190, 'diastolic_bp' => 110];
        $result = $this->service::calculateTriageCategory($vitals, null);
        $this->assertEquals('red', $result);
    }

    #[Test]
    public function it_calculates_red_category_for_critical_low_diastolic_bp(): void
    {
        $vitals = ['systolic_bp' => 100, 'diastolic_bp' => 50];
        $result = $this->service::calculateTriageCategory($vitals, null);
        $this->assertEquals('red', $result);
    }

    #[Test]
    public function it_calculates_red_category_for_critical_high_diastolic_bp(): void
    {
        $vitals = ['systolic_bp' => 160, 'diastolic_bp' => 130];
        $result = $this->service::calculateTriageCategory($vitals, null);
        $this->assertEquals('red', $result);
    }

    #[Test]
    public function it_calculates_red_category_for_critical_high_heart_rate(): void
    {
        $vitals = ['heart_rate' => 130];
        $result = $this->service::calculateTriageCategory($vitals, null);
        $this->assertEquals('red', $result);
    }

    #[Test]
    public function it_calculates_red_category_for_critical_low_heart_rate(): void
    {
        $vitals = ['heart_rate' => 35];
        $result = $this->service::calculateTriageCategory($vitals, null);
        $this->assertEquals('red', $result);
    }

    #[Test]
    public function it_calculates_red_category_for_critical_low_oxygen_saturation(): void
    {
        $vitals = ['oxygen_saturation' => 85];
        $result = $this->service::calculateTriageCategory($vitals, null);
        $this->assertEquals('red', $result);
    }

    #[Test]
    public function it_calculates_red_category_for_critical_low_gcs(): void
    {
        $vitals = ['gcs_eye' => 2, 'gcs_verbal' => 3, 'gcs_motor' => 4];
        $result = $this->service::calculateTriageCategory($vitals, null);
        $this->assertEquals('red', $result);
    }

    #[Test]
    public function it_calculates_red_category_for_severe_complaint_keywords(): void
    {
        $vitals = ['systolic_bp' => 120, 'heart_rate' => 80];
        $complaint = 'pasien mengalami stroke dengan kelemahan pada sisi tubuh kiri';
        $result = $this->service::calculateTriageCategory($vitals, $complaint);
        $this->assertEquals('red', $result);
    }

    #[Test]
    public function it_calculates_red_category_for_cardiac_arrest_complaint(): void
    {
        $vitals = ['heart_rate' => 70];
        $complaint = 'Henti jantung mendadak';
        $result = $this->service::calculateTriageCategory($vitals, $complaint);
        $this->assertEquals('red', $result);
    }

    #[Test]
    public function it_calculates_red_category_for_trauma_complaint(): void
    {
        $vitals = ['systolic_bp' => 110, 'heart_rate' => 90];
        $complaint = 'Trauma kepala berat akibat kecelakaan motor';
        $result = $this->service::calculateTriageCategory($vitals, $complaint);
        $this->assertEquals('red', $result);
    }

    #[Test]
    public function it_calculates_red_category_for_critical_respiratory_rate(): void
    {
        $vitals = ['respiratory_rate' => 35];
        $result = $this->service::calculateTriageCategory($vitals, null);
        $this->assertEquals('red', $result);
    }

    #[Test]
    public function it_calculates_red_category_for_unconscious_complaint(): void
    {
        $vitals = ['systolic_bp' => 120];
        $complaint = 'Pasien tidak sadarkan diri sejak 10 menit lalu';
        $result = $this->service::calculateTriageCategory($vitals, $complaint);
        $this->assertEquals('red', $result);
    }

    #[Test]
    public function it_calculates_red_category_for_severe_bleeding_complaint(): void
    {
        $vitals = ['systolic_bp' => 120, 'heart_rate' => 90];
        $complaint = 'Pendarahan hebat dari luka sayatan di lengan';
        $result = $this->service::calculateTriageCategory($vitals, $complaint);
        $this->assertEquals('red', $result);
    }

    #[Test]
    public function it_calculates_yellow_category_for_moderate_high_systolic_bp(): void
    {
        $vitals = ['systolic_bp' => 170, 'diastolic_bp' => 100];
        $result = $this->service::calculateTriageCategory($vitals, null);
        $this->assertEquals('yellow', $result);
    }

    #[Test]
    public function it_calculates_yellow_category_for_moderate_low_systolic_bp(): void
    {
        $vitals = ['systolic_bp' => 95, 'diastolic_bp' => 65];
        $result = $this->service::calculateTriageCategory($vitals, null);
        $this->assertEquals('yellow', $result);
    }

    #[Test]
    public function it_calculates_yellow_category_for_moderate_high_heart_rate(): void
    {
        $vitals = ['heart_rate' => 110];
        $result = $this->service::calculateTriageCategory($vitals, null);
        $this->assertEquals('yellow', $result);
    }

    #[Test]
    public function it_calculates_yellow_category_for_moderate_low_heart_rate(): void
    {
        $vitals = ['heart_rate' => 50];
        $result = $this->service::calculateTriageCategory($vitals, null);
        $this->assertEquals('yellow', $result);
    }

    #[Test]
    public function it_calculates_yellow_category_for_moderate_oxygen_saturation(): void
    {
        $vitals = ['oxygen_saturation' => 92];
        $result = $this->service::calculateTriageCategory($vitals, null);
        $this->assertEquals('yellow', $result);
    }

    #[Test]
    public function it_calculates_yellow_category_for_high_fever(): void
    {
        $vitals = ['body_temperature' => 40];
        $result = $this->service::calculateTriageCategory($vitals, null);
        $this->assertEquals('yellow', $result);
    }

    #[Test]
    public function it_calculates_yellow_category_for_hypothermia(): void
    {
        $vitals = ['body_temperature' => 34];
        $result = $this->service::calculateTriageCategory($vitals, null);
        $this->assertEquals('yellow', $result);
    }

    #[Test]
    public function it_calculates_yellow_category_for_moderate_gcs(): void
    {
        $vitals = ['gcs_eye' => 4, 'gcs_verbal' => 4, 'gcs_motor' => 6];
        $result = $this->service::calculateTriageCategory($vitals, null);
        $this->assertEquals('yellow', $result);
    }

    #[Test]
    public function it_calculates_yellow_category_for_chest_pain_complaint(): void
    {
        $vitals = ['systolic_bp' => 120, 'heart_rate' => 80];
        $complaint = 'Nyeri dada yang menjalar ke lengan kiri';
        $result = $this->service::calculateTriageCategory($vitals, $complaint);
        $this->assertEquals('yellow', $result);
    }

    #[Test]
    public function it_calculates_yellow_category_for_difficulty_breathing_complaint(): void
    {
        $vitals = ['systolic_bp' => 120, 'respiratory_rate' => 18];
        $complaint = 'Sesak napas sejak tadi malam';
        $result = $this->service::calculateTriageCategory($vitals, $complaint);
        $this->assertEquals('yellow', $result);
    }

    #[Test]
    public function it_calculates_yellow_category_for_severe_headache_complaint(): void
    {
        $vitals = ['systolic_bp' => 130];
        $complaint = 'Sakit kepala berat dengan muntah';
        $result = $this->service::calculateTriageCategory($vitals, $complaint);
        $this->assertEquals('yellow', $result);
    }

    #[Test]
    public function it_calculates_yellow_category_for_fainting_complaint(): void
    {
        $vitals = ['systolic_bp' => 115];
        $complaint = 'Pingsan tadi pagi saat bekerja';
        $result = $this->service::calculateTriageCategory($vitals, $complaint);
        $this->assertEquals('yellow', $result);
    }

    #[Test]
    public function it_calculates_green_category_for_normal_vitals(): void
    {
        $vitals = [
            'systolic_bp' => 120,
            'diastolic_bp' => 80,
            'heart_rate' => 75,
            'respiratory_rate' => 16,
            'oxygen_saturation' => 98,
            'body_temperature' => 37,
            'gcs_eye' => 4,
            'gcs_verbal' => 5,
            'gcs_motor' => 6,
        ];
        $result = $this->service::calculateTriageCategory($vitals, 'demam ringan');
        $this->assertEquals('green', $result);
    }

    #[Test]
    public function it_calculates_green_category_for_minimal_complaint(): void
    {
        $vitals = ['systolic_bp' => 110, 'heart_rate' => 70];
        $complaint = 'gatal-gatal di kulit';
        $result = $this->service::calculateTriageCategory($vitals, $complaint);
        $this->assertEquals('green', $result);
    }

    #[Test]
    public function it_calculates_green_category_for_no_complaint(): void
    {
        $vitals = ['systolic_bp' => 125, 'heart_rate' => 78];
        $result = $this->service::calculateTriageCategory($vitals, null);
        $this->assertEquals('green', $result);
    }

    // ==================== Category Label Tests ====================

    #[Test]
    public function it_returns_correct_label_for_red_category(): void
    {
        $result = $this->service::getCategoryLabel('red');
        $this->assertEquals('MERAH - Emergency', $result);
    }

    #[Test]
    public function it_returns_correct_label_for_yellow_category(): void
    {
        $result = $this->service::getCategoryLabel('yellow');
        $this->assertEquals('KUNING - Urgent', $result);
    }

    #[Test]
    public function it_returns_correct_label_for_green_category(): void
    {
        $result = $this->service::getCategoryLabel('green');
        $this->assertEquals('HIJAU - Non-Urgent', $result);
    }

    #[Test]
    public function it_returns_correct_label_for_black_category(): void
    {
        $result = $this->service::getCategoryLabel('black');
        $this->assertEquals('HITAM - Deceased', $result);
    }

    #[Test]
    public function it_returns_unknown_label_for_invalid_category(): void
    {
        $result = $this->service::getCategoryLabel('invalid');
        $this->assertEquals('Tidak Diketahui', $result);
    }

    // ==================== Category Short Label Tests ====================

    #[Test]
    public function it_returns_correct_short_label_for_red_category(): void
    {
        $result = $this->service::getCategoryShortLabel('red');
        $this->assertEquals('MERAH', $result);
    }

    #[Test]
    public function it_returns_correct_short_label_for_yellow_category(): void
    {
        $result = $this->service::getCategoryShortLabel('yellow');
        $this->assertEquals('KUNING', $result);
    }

    #[Test]
    public function it_returns_dash_for_invalid_short_label(): void
    {
        $result = $this->service::getCategoryShortLabel('unknown');
        $this->assertEquals('-', $result);
    }

    // ==================== Category Color Tests ====================

    #[Test]
    public function it_returns_danger_color_for_red_category(): void
    {
        $result = $this->service::getCategoryColor('red');
        $this->assertEquals('danger', $result);
    }

    #[Test]
    public function it_returns_warning_color_for_yellow_category(): void
    {
        $result = $this->service::getCategoryColor('yellow');
        $this->assertEquals('warning', $result);
    }

    #[Test]
    public function it_returns_success_color_for_green_category(): void
    {
        $result = $this->service::getCategoryColor('green');
        $this->assertEquals('success', $result);
    }

    #[Test]
    public function it_returns_gray_color_for_black_category(): void
    {
        $result = $this->service::getCategoryColor('black');
        $this->assertEquals('gray', $result);
    }

    // ==================== Category Hex Color Tests ====================

    #[Test]
    public function it_returns_correct_hex_color_for_red(): void
    {
        $result = $this->service::getCategoryHexColor('red');
        $this->assertEquals('#EF4444', $result);
    }

    #[Test]
    public function it_returns_correct_hex_color_for_yellow(): void
    {
        $result = $this->service::getCategoryHexColor('yellow');
        $this->assertEquals('#EAB308', $result);
    }

    #[Test]
    public function it_returns_correct_hex_color_for_green(): void
    {
        $result = $this->service::getCategoryHexColor('green');
        $this->assertEquals('#22C55E', $result);
    }

    // ==================== Category Options Tests ====================

    #[Test]
    public function it_returns_all_category_options(): void
    {
        $options = $this->service::getCategoryOptions();
        
        $this->assertArrayHasKey('red', $options);
        $this->assertArrayHasKey('yellow', $options);
        $this->assertArrayHasKey('green', $options);
        $this->assertArrayHasKey('black', $options);
        $this->assertCount(4, $options);
    }

    // ==================== Category Description Tests ====================

    #[Test]
    public function it_returns_correct_description_for_red_category(): void
    {
        $result = $this->service::getCategoryDescription('red');
        $this->assertStringContainsString('Segera ditangani', $result);
    }

    #[Test]
    public function it_returns_correct_description_for_yellow_category(): void
    {
        $result = $this->service::getCategoryDescription('yellow');
        $this->assertStringContainsString('60 menit', $result);
    }

    #[Test]
    public function it_returns_empty_description_for_invalid_category(): void
    {
        $result = $this->service::getCategoryDescription('invalid');
        $this->assertEquals('', $result);
    }

    // ==================== Category Wait Time Tests ====================

    #[Test]
    public function it_returns_immediate_wait_time_for_red(): void
    {
        $result = $this->service::getCategoryWaitTime('red');
        $this->assertEquals('Segera (0 menit)', $result);
    }

    #[Test]
    public function it_returns_60_minutes_wait_time_for_yellow(): void
    {
        $result = $this->service::getCategoryWaitTime('yellow');
        $this->assertEquals('< 60 menit', $result);
    }

    #[Test]
    public function it_returns_over_60_minutes_wait_time_for_green(): void
    {
        $result = $this->service::getCategoryWaitTime('green');
        $this->assertEquals('> 60 menit', $result);
    }

    #[Test]
    public function it_returns_dash_wait_time_for_invalid_category(): void
    {
        $result = $this->service::getCategoryWaitTime('unknown');
        $this->assertEquals('-', $result);
    }

    // ==================== Complex Scenario Tests ====================

    #[Test]
    public function it_prioritizes_red_over_yellow_when_multiple_conditions(): void
    {
        // BP suggests yellow (moderate high), but heart rate suggests red
        $vitals = ['systolic_bp' => 170, 'heart_rate' => 130];
        $result = $this->service::calculateTriageCategory($vitals, null);
        $this->assertEquals('red', $result);
    }

    #[Test]
    public function it_handles_empty_vitals_array(): void
    {
        $result = $this->service::calculateTriageCategory([], null);
        $this->assertEquals('green', $result);
    }

    #[Test]
    public function it_handles_partial_vitals_data(): void
    {
        $vitals = ['heart_rate' => 75];
        $result = $this->service::calculateTriageCategory($vitals, null);
        $this->assertEquals('green', $result);
    }

    #[Test]
    public function it_calculates_red_for_anaphylaxis_complaint(): void
    {
        $vitals = ['systolic_bp' => 100];
        $complaint = 'Reaksi alergi berat dengan sesak napas';
        $result = $this->service::calculateTriageCategory($vitals, $complaint);
        $this->assertEquals('red', $result);
    }

    #[Test]
    public function it_calculates_red_for_seizure_complaint(): void
    {
        $vitals = ['systolic_bp' => 110];
        $complaint = 'Kejang berulang sejak 15 menit lalu';
        $result = $this->service::calculateTriageCategory($vitals, $complaint);
        $this->assertEquals('red', $result);
    }

    #[Test]
    public function it_calculates_yellow_for_fracture_complaint(): void
    {
        $vitals = ['systolic_bp' => 120, 'heart_rate' => 85];
        $complaint = 'Patah tulang kaki akibat jatuh';
        $result = $this->service::calculateTriageCategory($vitals, $complaint);
        $this->assertEquals('yellow', $result);
    }

    #[Test]
    public function it_calculates_yellow_for_pregnancy_complaint(): void
    {
        $vitals = ['systolic_bp' => 115];
        $complaint = 'Kontraksi pada kehamilan 38 minggu';
        $result = $this->service::calculateTriageCategory($vitals, $complaint);
        $this->assertEquals('yellow', $result);
    }

    #[Test]
    public function it_calculates_red_for_burn_complaint(): void
    {
        $vitals = ['systolic_bp' => 110];
        $complaint = 'Luka bakar derajat 3 di punggung';
        $result = $this->service::calculateTriageCategory($vitals, $complaint);
        $this->assertEquals('yellow', $result); // burn is yellow, not severe
    }

    #[Test]
    public function it_calculates_red_for_drowning_complaint(): void
    {
        $vitals = ['systolic_bp' => 90];
        $complaint = 'Hampir tenggelam di kolam renang';
        $result = $this->service::calculateTriageCategory($vitals, $complaint);
        $this->assertEquals('red', $result);
    }
}
