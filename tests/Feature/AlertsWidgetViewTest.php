<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Widgets\AlertsWidget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AlertsWidgetViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_alerts_widget_uses_explicit_icon_size_styles(): void
    {
        $html = Livewire::test(AlertsWidget::class)->html();

        $this->assertStringContainsString('class="rs-alert-icon"', $html);
        $this->assertStringContainsString('width: 1.25rem;', $html);
        $this->assertStringNotContainsString('class="h-5 w-5"', $html);
    }
}
