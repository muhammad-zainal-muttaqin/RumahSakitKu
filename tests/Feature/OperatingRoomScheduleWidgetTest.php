<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Resources\SurgeryResource\Widgets\OperatingRoomSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OperatingRoomScheduleWidgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_operating_room_schedule_widget_renders_without_heading_error(): void
    {
        $html = Livewire::test(OperatingRoomSchedule::class)->html();

        $this->assertStringContainsString('Jadwal OK Hari Ini', $html);
        $this->assertStringContainsString('OK Aktif', $html);
        $this->assertStringContainsString('width: 1rem; height: 1rem;', $html);
    }
}
