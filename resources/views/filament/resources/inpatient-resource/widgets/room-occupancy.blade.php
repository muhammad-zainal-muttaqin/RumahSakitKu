<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-filament::icon
                    icon="heroicon-o-chart-bar"
                    style="width: 1.25rem; height: 1.25rem; color: #6b7280;"
                />
                Tingkat Hunian Kamar
            </div>
        </x-slot>

        <div class="space-y-4">
            {{-- Total Stats --}}
            <div class="grid grid-cols-4 gap-4 p-4 bg-gray-50 rounded-lg dark:bg-gray-800">
                <div class="text-center">
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ $totalStats['total_beds'] }}
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Total Bed</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-success-600">
                        {{ $totalStats['available_beds'] }}
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Tersedia</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-danger-600">
                        {{ $totalStats['occupied_beds'] }}
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Terisi</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold {{ $totalStats['occupancy_rate'] >= 80 ? 'text-danger-600' : ($totalStats['occupancy_rate'] >= 50 ? 'text-warning-600' : 'text-success-600') }}">
                        {{ $totalStats['occupancy_rate'] }}%
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Tingkat Hunian</div>
                </div>
            </div>

            {{-- Per Class Stats --}}
            <div class="grid grid-cols-3 md:grid-cols-5 lg:grid-cols-9 gap-3">
                @foreach($occupancyData as $class => $data)
                    @if($data['total_beds'] > 0)
                        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-3">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ $class }}</span>
                                <x-filament::badge :color="$data['color']" size="sm">
                                    {{ $data['occupancy_rate'] }}%
                                </x-filament::badge>
                            </div>
                            
                            <div class="space-y-1">
                                <div class="flex justify-between text-xs">
                                    <span class="text-gray-500">Terisi:</span>
                                    <span class="font-medium">{{ $data['occupied_beds'] }}</span>
                                </div>
                                <div class="flex justify-between text-xs">
                                    <span class="text-gray-500">Tersedia:</span>
                                    <span class="font-medium text-success-600">{{ $data['available_beds'] }}</span>
                                </div>
                                <div class="flex justify-between text-xs">
                                    <span class="text-gray-500">Total:</span>
                                    <span class="font-medium">{{ $data['total_beds'] }}</span>
                                </div>
                            </div>

                            {{-- Progress Bar --}}
                            <div class="mt-2">
                                <div class="w-full bg-gray-200 rounded-full h-1.5 dark:bg-gray-700">
                                    <div 
                                        class="h-1.5 rounded-full transition-all duration-300
                                            {{ $data['occupancy_rate'] >= 80 ? 'bg-danger-500' : ($data['occupancy_rate'] >= 50 ? 'bg-warning-500' : 'bg-success-500') }}"
                                        style="width: {{ $data['occupancy_rate'] }}%"
                                    ></div>
                                </div>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>

            {{-- Legend --}}
            <div class="flex flex-wrap gap-4 text-xs text-gray-500 dark:text-gray-400">
                <div class="flex items-center gap-1">
                    <div class="w-3 h-3 rounded-full bg-success-500"></div>
                    <span>Hunian Normal (&lt;50%)</span>
                </div>
                <div class="flex items-center gap-1">
                    <div class="w-3 h-3 rounded-full bg-warning-500"></div>
                    <span>Hunian Sedang (50-80%)</span>
                </div>
                <div class="flex items-center gap-1">
                    <div class="w-3 h-3 rounded-full bg-danger-500"></div>
                    <span>Hunian Tinggi (&gt;80%)</span>
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
