<x-filament-panels::page>
    {{-- Filters --}}
    <x-filament-panels::form wire:submit="updateFilters">
        {{ $this->form }}
    </x-filament-panels::form>

    @php
        $summary = $this->getSummaryData();
    @endphp

    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="rounded-xl bg-primary-50 p-4 text-center dark:bg-primary-900/20">
            <p class="text-3xl font-bold text-primary-600 dark:text-primary-400">
                {{ number_format($summary['total_cases']) }}
            </p>
            <p class="mt-1 text-sm font-medium text-gray-600 dark:text-gray-400">Total Kasus</p>
        </div>

        <div class="rounded-xl bg-success-50 p-4 text-center dark:bg-success-900/20">
            <p class="text-3xl font-bold text-success-600 dark:text-success-400">
                {{ number_format($summary['total_diseases']) }}
            </p>
            <p class="mt-1 text-sm font-medium text-gray-600 dark:text-gray-400">Jenis Penyakit</p>
        </div>

        <div class="rounded-xl bg-info-50 p-4 text-center dark:bg-info-900/20">
            <p class="text-3xl font-bold text-info-600 dark:text-info-400">
                {{ number_format($summary['average_cases_per_disease'], 2) }}
            </p>
            <p class="mt-1 text-sm font-medium text-gray-600 dark:text-gray-400">Rata-rata Kasus/Penyakit</p>
        </div>
    </div>

    {{-- Top 10 Diseases Chart --}}
    @if(count($summary['top_10']) > 0)
        <x-filament::section class="mt-6">
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-chart-bar class="h-5 w-5" />
                    10 Besar Penyakit
                </div>
            </x-slot>

            <div class="space-y-3">
                @foreach($summary['top_10'] as $index => $disease)
                    <div class="flex items-center gap-4">
                        <span class="w-8 text-center font-bold text-gray-500 dark:text-gray-400">
                            {{ $index + 1 }}
                        </span>
                        <div class="flex-1">
                            <div class="flex items-center justify-between">
                                <span class="font-medium text-gray-900 dark:text-white">
                                    {{ $disease->primary_diagnosis_name }}
                                </span>
                                <span class="text-sm font-semibold text-primary-600 dark:text-primary-400">
                                    {{ number_format($disease->count) }} kasus
                                </span>
                            </div>
                            <div class="mt-1 h-2 w-full rounded-full bg-gray-200 dark:bg-gray-700">
                                <div 
                                    class="h-2 rounded-full bg-primary-500 transition-all duration-500"
                                    style="width: {{ min(100, ($disease->count / $summary['top_10'][0]->count) * 100) }}%"
                                ></div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-filament::section>
    @endif

    {{-- Detailed Table --}}
    <x-filament::section class="mt-6">
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-heroicon-o-table-cells class="h-5 w-5" />
                Detail Penyakit
            </div>
        </x-slot>

        {{ $this->table }}
    </x-filament::section>
</x-filament-panels::page>
