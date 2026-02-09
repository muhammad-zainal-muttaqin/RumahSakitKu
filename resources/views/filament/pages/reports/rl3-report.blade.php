<x-filament-panels::page>
    {{-- Filters --}}
    <x-filament-panels::form wire:submit="updateFilters">
        {{ $this->form }}
    </x-filament-panels::form>

    @php
        $reportData = $this->getReportData();
    @endphp

    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-heroicon-o-clipboard-document-list class="h-5 w-5" />
                {{ $reportData['title'] }}
            </div>
        </x-slot>

        @if(isset($reportData['data']))
            <div class="space-y-4">
                @foreach($reportData['data'] as $key => $value)
                    @if(!is_array($value) && !is_object($value))
                        <div class="flex items-center justify-between border-b border-gray-200 py-2 last:border-0 dark:border-gray-700">
                            <span class="text-gray-600 dark:text-gray-400">
                                {{ ucwords(str_replace('_', ' ', $key)) }}
                            </span>
                            <span class="font-semibold text-gray-900 dark:text-white">
                                @if(is_numeric($value))
                                    @if(str_contains($key, 'rate') || str_contains($key, 'bor') || str_contains($key, 'gdr') || str_contains($key, 'ndr'))
                                        {{ number_format($value, 2) }}%
                                    @else
                                        {{ number_format($value) }}
                                    @endif
                                @else
                                    {{ $value }}
                                @endif
                            </span>
                        </div>
                    @elseif(is_array($value) && !empty($value))
                        <div class="mt-4">
                            <h4 class="mb-2 font-medium text-gray-900 dark:text-white">
                                {{ ucwords(str_replace('_', ' ', $key)) }}
                            </h4>
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="border-b border-gray-200 dark:border-gray-700">
                                            <th class="py-2 text-left font-medium text-gray-600 dark:text-gray-400">Item</th>
                                            <th class="py-2 text-right font-medium text-gray-600 dark:text-gray-400">Nilai</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($value as $subKey => $subValue)
                                            <tr class="border-b border-gray-100 last:border-0 dark:border-gray-800">
                                                <td class="py-2 text-gray-700 dark:text-gray-300">
                                                    @if(is_string($subKey))
                                                        {{ $subKey }}
                                                    @else
                                                        {{ $loop->iteration }}
                                                    @endif
                                                </td>
                                                <td class="py-2 text-right font-medium text-gray-900 dark:text-white">
                                                    @if(is_numeric($subValue))
                                                        {{ number_format($subValue) }}
                                                    @elseif(is_object($subValue) && isset($subValue->room_class))
                                                        {{ $subValue->room_class }}: {{ $subValue->bed_count ?? $subValue->room_count }} bed
                                                    @else
                                                        {{ $subValue }}
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        @else
            <div class="py-8 text-center text-gray-500 dark:text-gray-400">
                <x-heroicon-o-inbox class="mx-auto h-12 w-12 opacity-50" />
                <p class="mt-2">Tidak ada data untuk periode yang dipilih</p>
            </div>
        @endif
    </x-filament::section>
</x-filament-panels::page>
